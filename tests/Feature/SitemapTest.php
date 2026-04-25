<?php

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\TagCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns sitemap index xml with all sitemap links', function () {
    $response = $this->get(route('sitemap.index'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml');
    $response->assertSee('<sitemapindex', false);
    $response->assertSee(route('sitemap.static'), false);
    $response->assertSee(route('sitemap.posts'), false);
    $response->assertSee(route('sitemap.tags'), false);
    $response->assertSee(route('sitemap.users'), false);
});

it('returns static sitemap as urlset without lastmod elements', function () {
    $response = $this->get(route('sitemap.static'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml');
    $response->assertSee('<urlset', false);
    $response->assertSee(route('home'), false);
    $response->assertSee(route('posts.index'), false);
    $response->assertSee(route('tags'), false);
    $response->assertDontSee('<lastmod>', false);
});

it('returns posts sitemap with listed posts only and includes lastmod', function () {
    $listedPost = Post::factory()->create(['is_listed' => true]);
    $unlistedPost = Post::factory()->unlisted()->create();

    $response = $this->get(route('sitemap.posts'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml');
    $response->assertSee('<urlset', false);
    $response->assertSee(route('posts.show', $listedPost), false);
    $response->assertDontSee(route('posts.show', $unlistedPost), false);
    $response->assertSee('<lastmod>', false);
});

it('returns tags sitemap entries', function () {
    $tag = Tag::factory()->create([
        'name' => 'reaction_image',
        'category' => TagCategory::General,
    ]);

    $response = $this->get(route('sitemap.tags'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml');
    $response->assertSee('<urlset', false);
    $response->assertSee(route('tags.show', ['category' => $tag->category, 'tag' => $tag]), false);
    $response->assertSee('<lastmod>', false);
});

it('returns users sitemap entries', function () {
    $user = User::factory()->create(['username' => 'sitemap_user']);

    $response = $this->get(route('sitemap.users'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml');
    $response->assertSee('<urlset', false);
    $response->assertSee(route('users.show', $user), false);
    $response->assertSee('<lastmod>', false);
});
