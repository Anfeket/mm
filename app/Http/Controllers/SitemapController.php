<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

use App\Models\Post;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $latestPost = Post::where('is_listed', true)
            ->latest('updated_at')
            ->value('updated_at');

        $sitemaps = [
            [
                'loc' => route('sitemap.static'),
                'lastmod' => null,
            ],
            [
                'loc' => route('sitemap.posts'),
                'lastmod' => $latestPost ? $latestPost->toAtomString() : null,
            ],
        ];
        return response()
            ->view('sitemap.index', ['sitemaps' => $sitemaps])
            ->header('Content-Type', 'application/xml');
    }

    public function static(): Response
    {
        $urls = [
            route('home'),
            route('posts.index'),
        ];
        return response()
            ->view('sitemap.static', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    public function posts(): Response
    {
        $posts = Post::where('is_listed', true)
            ->latest('updated_at')
            ->get(['id', 'updated_at'])
            ->map(function (Post $post): array {
                return [
                    'loc' => route('posts.show', $post),
                    'lastmod' => $post->updated_at->toAtomString(),
                ];
            })
            ->all();
        return response()
            ->view('sitemap.posts', ['posts' => $posts])
            ->header('Content-Type', 'application/xml');
    }
}
