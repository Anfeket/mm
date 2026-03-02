<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\Request;

class TagController extends Controller
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Tag $tag)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tag $tag)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tag $tag)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tag $tag)
    {
        //
    }

    public function autocomplete(Request $request, TagService $tagService)
    {
        $query = $request->input('q');
        if (empty($query)) {
            return response()->json([]);
        }

        $tags = $tagService->searchTags($query);
        return response()->json(
            $tags->map(fn(Tag $tag) => [
                'name' => $tag->name,
                'category' => $tag->category->value,
                'post_count' => $tag->post_count,
                'alias_of' => $tag->aliasTag?->name,
            ])
        );
    }
}
