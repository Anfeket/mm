<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\Request;

class PostTagController extends Controller
{
    public function attach(Request $request, Post $post, TagService $tagService)
    {
        if ($request->user()->cannot('editTags', $post)) {
            abort(403);
        }

        $request->validate([
            'tag' => ['required', 'string', 'max:255'],
        ]);

        $parsed = $tagService->parseSingleTag($request->input('tag'));

        if ($parsed === null) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Invalid tag.'], 422);
            }
            return back()->withErrors(['tag' => 'Invalid tag.']);
        }

        $tag = $tagService->findOrCreate($parsed['name'], $parsed['category']);

        if (!$post->tags()->where('tag_id', $tag->id)->exists()) {
            $post->tags()->attach($tag->id, ['added_by_user_id' => $request->user()->id]);
            $tag->increment('post_count');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'id'       => $tag->id,
                'name'     => $tag->name,
                'category' => $tag->category->value,
            ], 201);
        }

        return redirect()->route('posts.show', $post);
    }

    public function detach(Request $request, Post $post, Tag $tag)
    {
        if ($request->user()->cannot('editTags', $post)) {
            abort(403);
        }

        $detached = $post->tags()->detach($tag->id);

        if ($detached) {
            $tag->decrement('post_count');
        }

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->route('posts.show', $post);
    }
}
