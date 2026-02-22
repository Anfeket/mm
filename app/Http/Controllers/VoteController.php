<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VoteController extends Controller
{
    public function vote(Request $request, Post $post): RedirectResponse
    {
        $request->validate([
            'value' => ['required', 'integer', 'in:-1,1'],
        ]);

        $value      = (int)$request->input('value');
        $existing   = $post->votes()->where('user_id', $request->user()->id)->first();

        if ($existing) {
            if ($existing->value === $value) {
                $existing->delete();
            } else {
                $existing->update(['value' => $value]);
            }
        } else {
            $post->votes()->create([
                'user_id' => $request->user()->id,
                'value' => $value,
            ]);
        }

        $post->like_count = $post->votes()->sum('value');
        $post->save();

        return back();
    }
}
