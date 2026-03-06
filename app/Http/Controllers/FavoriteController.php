<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function toggle(Request $request, Post $post): RedirectResponse
    {
        $user = $request->user();

        if ($post->favorites()->where('user_id', $user->id)->exists()) {
            $post->favorites()->detach($user->id);
        } else {
            $post->favorites()->attach($user->id);
        }

        $post->favorites_count = $post->favorites()->count();
        $post->save();

        return back();
    }
}
