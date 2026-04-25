<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Response;

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
            [
                'loc' => route('sitemap.tags'),
                'lastmod' => Tag::latest('updated_at')->value('updated_at')?->toAtomString(),
            ],
            [
                'loc' => route('sitemap.users'),
                'lastmod' => User::latest('updated_at')->value('updated_at')?->toAtomString(),
            ],
        ];

        return response()
            ->view('sitemap.index', ['sitemaps' => $sitemaps])
            ->header('Content-Type', 'application/xml');
    }

    public function static(): Response
    {
        $urls = [
            ['loc' => route('home')],
            ['loc' => route('posts.index')],
            ['loc' => route('tags')],
        ];

        return response()
            ->view('sitemap.urlset', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    public function posts(): Response
    {
        $urls = Post::where('is_listed', true)
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
            ->view('sitemap.urlset', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    public function tags(): Response
    {
        $urls = Tag::latest('updated_at')
            ->get(['id', 'name', 'category', 'updated_at'])
            ->map(function (Tag $tag): array {
                return [
                    'loc' => route('tags.show', ['category' => $tag->category, 'tag' => $tag]),
                    'lastmod' => $tag->updated_at->toAtomString(),
                ];
            })
            ->all();

        return response()
            ->view('sitemap.urlset', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    public function users(): Response
    {
        $urls = User::latest('updated_at')
            ->get(['id', 'username', 'updated_at'])
            ->map(function (User $user): array {
                return [
                    'loc' => route('users.show', $user),
                    'lastmod' => $user->updated_at->toAtomString(),
                ];
            })
            ->all();

        return response()
            ->view('sitemap.urlset', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
