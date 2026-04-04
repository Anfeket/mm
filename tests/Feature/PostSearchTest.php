<?php

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\TagCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['username' => 'Admin']);
    $this->other = User::factory()->create(['username' => 'Bob']);
});

// ---------------------------------------------------------------------------
// Score filtering
// ---------------------------------------------------------------------------

it('filters posts by score greater than', function () {
    Post::factory()->create(['author_id' => $this->user->id, 'like_count' => 10, 'is_listed' => true]);
    Post::factory()->create(['author_id' => $this->user->id, 'like_count' => 3, 'is_listed' => true]);

    $this->get(route('posts.index', ['q' => 'score:>5']))
        ->assertOk()
        ->assertViewHas('posts', fn ($posts) => $posts->total() === 1);
});

it('filters posts by score less than', function () {
    Post::factory()->create(['author_id' => $this->user->id, 'like_count' => 10, 'is_listed' => true]);
    Post::factory()->create(['author_id' => $this->user->id, 'like_count' => 3, 'is_listed' => true]);

    $this->get(route('posts.index', ['q' => 'score:<5']))
        ->assertOk()
        ->assertViewHas('posts', fn ($posts) => $posts->total() === 1);
});

it('filters posts by score range', function () {
    Post::factory()->create(['author_id' => $this->user->id, 'like_count' => 10, 'is_listed' => true]);
    Post::factory()->create(['author_id' => $this->user->id, 'like_count' => 5, 'is_listed' => true]);
    Post::factory()->create(['author_id' => $this->user->id, 'like_count' => 1, 'is_listed' => true]);

    $this->get(route('posts.index', ['q' => 'score:4..9']))
        ->assertOk()
        ->assertViewHas('posts', fn ($posts) => $posts->total() === 1);
});

it('filters posts by exact score', function () {
    Post::factory()->create(['author_id' => $this->user->id, 'like_count' => 5, 'is_listed' => true]);
    Post::factory()->create(['author_id' => $this->user->id, 'like_count' => 3, 'is_listed' => true]);

    $this->get(route('posts.index', ['q' => 'score:5']))
        ->assertOk()
        ->assertViewHas('posts', fn ($posts) => $posts->total() === 1);
});

// ---------------------------------------------------------------------------
// Views filtering
// ---------------------------------------------------------------------------

it('filters posts by views', function () {
    Post::factory()->create(['author_id' => $this->user->id, 'view_count' => 100, 'is_listed' => true]);
    Post::factory()->create(['author_id' => $this->user->id, 'view_count' => 5, 'is_listed' => true]);

    $this->get(route('posts.index', ['q' => 'views:>50']))
        ->assertOk()
        ->assertViewHas('posts', fn ($posts) => $posts->total() === 1);
});

// ---------------------------------------------------------------------------
// Uploader filtering
// ---------------------------------------------------------------------------

it('filters posts by uploader', function () {
    Post::factory()->create(['author_id' => $this->user->id, 'is_listed' => true]);
    Post::factory()->create(['author_id' => $this->other->id, 'is_listed' => true]);

    $this->get(route('posts.index', ['q' => 'uploader:Admin']))
        ->assertOk()
        ->assertViewHas('posts', fn ($posts) => $posts->total() === 1);
});

it('excludes posts by negated uploader', function () {
    Post::factory()->create(['author_id' => $this->user->id, 'is_listed' => true]);
    Post::factory()->create(['author_id' => $this->other->id, 'is_listed' => true]);

    $this->get(route('posts.index', ['q' => '-uploader:Admin']))
        ->assertOk()
        ->assertViewHas('posts', fn ($posts) => $posts->total() === 1);
});

// ---------------------------------------------------------------------------
// Artist filtering
// ---------------------------------------------------------------------------

it('filters posts by artist metatag', function () {
    $post = Post::factory()->create(['author_id' => $this->user->id, 'is_listed' => true]);
    $other = Post::factory()->create(['author_id' => $this->user->id, 'is_listed' => true]);

    $tag = Tag::factory()->create(['name' => 'john_doe', 'category' => TagCategory::Artist]);
    $post->tags()->attach($tag->id, ['added_by_user_id' => $this->user->id]);

    $this->get(route('posts.index', ['q' => 'artist:john_doe']))
        ->assertOk()
        ->assertViewHas('posts', fn ($posts) => $posts->total() === 1);
});

// ---------------------------------------------------------------------------
// Date filtering
// ---------------------------------------------------------------------------

it('filters posts by year', function () {
    Post::factory()->create(['author_id' => $this->user->id, 'is_listed' => true, 'created_at' => '2024-06-01']);
    Post::factory()->create(['author_id' => $this->user->id, 'is_listed' => true, 'created_at' => '2023-06-01']);

    $this->get(route('posts.index', ['q' => 'date:2024']))
        ->assertOk()
        ->assertViewHas('posts', fn ($posts) => $posts->total() === 1);
});

it('filters posts by year and month', function () {
    Post::factory()->create(['author_id' => $this->user->id, 'is_listed' => true, 'created_at' => '2024-06-01']);
    Post::factory()->create(['author_id' => $this->user->id, 'is_listed' => true, 'created_at' => '2024-07-01']);

    $this->get(route('posts.index', ['q' => 'date:2024/06']))
        ->assertOk()
        ->assertViewHas('posts', fn ($posts) => $posts->total() === 1);
});

it('filters posts by date greater than', function () {
    Post::factory()->create(['author_id' => $this->user->id, 'is_listed' => true, 'created_at' => '2024-06-01']);
    Post::factory()->create(['author_id' => $this->user->id, 'is_listed' => true, 'created_at' => '2024-01-01']);

    $this->get(route('posts.index', ['q' => 'date:>2024-03-01']))
        ->assertOk()
        ->assertViewHas('posts', fn ($posts) => $posts->total() === 1);
});

// ---------------------------------------------------------------------------
// Order filtering
// ---------------------------------------------------------------------------

it('orders posts by score descending', function () {
    $low = Post::factory()->create(['author_id' => $this->user->id, 'like_count' => 1, 'is_listed' => true]);
    $high = Post::factory()->create(['author_id' => $this->user->id, 'like_count' => 10, 'is_listed' => true]);

    $response = $this->get(route('posts.index', ['q' => 'order:score']));
    $posts = $response->viewData('posts');

    expect($posts->first()->id)->toBe($high->id);
});

it('orders posts by score ascending', function () {
    $low = Post::factory()->create(['author_id' => $this->user->id, 'like_count' => 1, 'is_listed' => true]);
    $high = Post::factory()->create(['author_id' => $this->user->id, 'like_count' => 10, 'is_listed' => true]);

    $response = $this->get(route('posts.index', ['q' => 'order:score_asc']));
    $posts = $response->viewData('posts');

    expect($posts->first()->id)->toBe($low->id);
});

it('uses last order filter when multiple are specified', function () {
    $low = Post::factory()->create(['author_id' => $this->user->id, 'like_count' => 1, 'view_count' => 100, 'is_listed' => true]);
    $high = Post::factory()->create(['author_id' => $this->user->id, 'like_count' => 10, 'view_count' => 1, 'is_listed' => true]);

    $response = $this->get(route('posts.index', ['q' => 'order:score order:views']));
    $posts = $response->viewData('posts');

    expect($posts->first()->id)->toBe($low->id); // views wins
});

// ---------------------------------------------------------------------------
// Combined filters
// ---------------------------------------------------------------------------

it('combines tag search with meta filters', function () {
    $post = Post::factory()->create(['author_id' => $this->user->id, 'like_count' => 10, 'is_listed' => true]);
    $other = Post::factory()->create(['author_id' => $this->user->id, 'like_count' => 1, 'is_listed' => true]);

    $tag = Tag::factory()->general()->create(['name' => 'funny']);
    $post->tags()->attach($tag->id, ['added_by_user_id' => $this->user->id]);
    $other->tags()->attach($tag->id, ['added_by_user_id' => $this->user->id]);

    $this->get(route('posts.index', ['q' => 'funny score:>5']))
        ->assertOk()
        ->assertViewHas('posts', fn ($posts) => $posts->total() === 1);
});
