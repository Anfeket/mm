<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Post $post)
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        // Strip surrounding whitespace and reject blank content
        $content = trim($validated['content']);
        if ($content === '') {
            return back()->withErrors(['content' => 'Comment cannot be empty.'])->withInput();
        }

        $post->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $content,
        ]);

        return redirect()->route('posts.show', $post)->with('success', 'Comment posted.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Comment $comment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Comment $comment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Comment $comment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comment $comment)
    {
        abort_unless(Auth::id() === $comment->user_id, 403);

        $post_id = $comment->post_id;
        $comment->delete();

        return redirect()->route('posts.show', $post_id);
    }
}
