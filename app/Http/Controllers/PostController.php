<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = Post::where('is_listed', true)
            ->latest()
            ->paginate(20);
        return view('post.index', compact('posts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('post.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        $post = $post->load('author', 'tags', 'comments');

        // Find previous and next posts by ID
        $previousPost = Post::where('is_listed', true)
            ->where('id', '<', $post->id)
            ->orderBy('id', 'desc')
            ->first();
        $nextPost = Post::where('is_listed', true)
            ->where('id', '>', $post->id)
            ->orderBy('id', 'asc')
            ->first();

        return view('post.show', compact('post', 'previousPost', 'nextPost'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        //
    }
}
