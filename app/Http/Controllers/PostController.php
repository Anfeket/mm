<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPostMedia;
use App\Models\Post;
use App\PostProcessingStatus;
use App\Services\FileStorageService;
use App\Services\TagService;
use App\Support\JsonLd;
use App\TagCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, TagService $tagService)
    {
        $query = Post::where('is_listed', true)->latest();

        if ($request->filled('q')) {
            $tags = $tagService->parseSearchInput($request->input('q'));

            foreach ($tags['include'] as $tag) {
                $query->whereHas(
                    'tags',
                    fn ($q) => $q
                        ->where('name', $tag['name'])
                        ->where('category', $tag['category'])
                );
            }

            foreach ($tags['exclude'] as $tag) {
                $query->whereDoesntHave(
                    'tags',
                    fn ($q) => $q
                        ->where('name', $tag['name'])
                        ->where('category', $tag['category'])
                );
            }
        }

        $jsonLd = JsonLd::forSite();

        $posts = $query->paginate(20)->withQueryString();

        return view('post.index', compact('posts', 'jsonLd'));
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
    public function store(Request $request, FileStorageService $storage, TagService $tagService)
    {
        $request->validate([
            'file' => ['required_without:url', 'prohibits:url', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm', 'max:102400'], // 100MB max
            'url' => ['required_without:file', 'prohibits:file', 'nullable', 'url', 'max:2048'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'description' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'string'],
            'artist' => ['nullable', 'string'],
        ]);

        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileInfo = $storage->store($file);
            } elseif ($request->filled('url')) {
                $file = $storage->fileFromUrl($request->input('url'));
                $fileInfo = $storage->store($file);
            } else {
                return back()->withErrors(['file' => 'No file or URL provided'])->withInput();
            }
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Failed to process the file: '.$e->getMessage()])->withInput();
        }

        $existing = Post::where('file_hash', $fileInfo['file_hash'])->first();
        if ($existing) {
            return back()
                ->withErrors(['file' => "Duplicate of post #{$existing->id}"])
                ->withInput();
        }

        $mimeType = $fileInfo['mime_type'];

        $width = $height = null;
        if (str_starts_with($mimeType, 'image/')) {
            [$width, $height] = getimagesize($file->getRealPath());
        }

        $post = Post::create([
            'author_id' => $request->user()->id,
            'file_path' => $fileInfo['file_path'],
            'file_hash' => $fileInfo['file_hash'],
            'file_size' => $fileInfo['file_size'],
            'mime_type' => $mimeType,
            'original_filename' => $fileInfo['original_filename'],
            'width' => $width,
            'height' => $height,
            'description' => $request->input('description'),
            'source_url' => $request->input('source_url'),
            'is_listed' => false, // New posts are unlisted by default
            'processing_status' => PostProcessingStatus::Processing,
        ]);

        // If uploaded via URL, clean up temp file after all usage
        if ($request->filled('url') && isset($file) && file_exists($file->getRealPath())) {
            @unlink($file->getRealPath());
        }

        // Handle tags
        if ($request->filled('artist')) {
            $tagService->syncPostTags($post, $tagService->parseInput($request->input('artist'), TagCategory::Artist));
        }
        if ($request->filled('tags')) {
            $tagService->syncPostTags($post, $tagService->parseInput($request->input('tags')));
        }

        ProcessPostMedia::dispatch($post);

        return redirect()->route('posts.show', $post)->with('success', 'Post uploaded successfully! It will be listed once processing is complete.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        $post = $post->load(['tags', 'comments' => fn ($q) => $q->with('user')->latest()->limit(500)]);

        $viewKey = 'view_'.md5(request()->ip().request()->userAgent().$post->id);
        $isBot = preg_match('/bot|crawl|spider|slurp|bingbot|googlebot/i', request()->userAgent());
        if (! $isBot && ! Cache::has($viewKey)) {
            $post->increment('view_count');
            Cache::put($viewKey, true, now()->addHours(12));
        }

        $upvotes = $post->upvotes;
        $downvotes = $post->downvotes;
        $userVote = Auth::check()
            ? $post->votes()->where('user_id', Auth::id())->value('value')
            : null;
        $userFavorite = Auth::check()
            ? $post->favorites()->where('user_id', Auth::id())->exists()
            : false;

        // Find previous and next posts by ID
        $previousPost = Post::where('is_listed', true)
            ->where('id', '<', $post->id)
            ->orderBy('id', 'desc')
            ->first();
        $nextPost = Post::where('is_listed', true)
            ->where('id', '>', $post->id)
            ->orderBy('id', 'asc')
            ->first();

        $jsonLd = JsonLd::forPost($post);

        return view('post.show', compact(
            'post',
            'previousPost',
            'nextPost',
            'upvotes',
            'downvotes',
            'userVote',
            'userFavorite',
            'jsonLd'
        ));
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
