<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('increments view count without touching post updated_at on show', function () {
    $post = Post::factory()->create([
        'is_listed' => true,
        'view_count' => 0,
    ]);

    $originalUpdatedAt = $post->updated_at;

    $this->get(route('posts.show', $post))->assertOk();

    $post->refresh();

    expect($post->view_count)->toBe(1)
        ->and($post->updated_at->equalTo($originalUpdatedAt))->toBeTrue();
});

it('updates like_count without touching post updated_at when voting', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create([
        'author_id' => $user->id,
        'is_listed' => true,
        'updated_at' => now()->subDay(),
    ]);

    $originalUpdatedAt = $post->updated_at;

    $this->actingAs($user)
        ->post(route('posts.vote', $post), ['value' => 1])
        ->assertRedirect();

    $post->refresh();

    expect($post->like_count)->toBe(1)
        ->and($post->updated_at->equalTo($originalUpdatedAt))->toBeTrue();
});

it('updates favorites_count without touching post updated_at when toggling favorite', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create([
        'author_id' => $user->id,
        'is_listed' => true,
        'updated_at' => now()->subDay(),
    ]);

    $originalUpdatedAt = $post->updated_at;

    $this->actingAs($user)
        ->post(route('posts.favorites.toggle', $post))
        ->assertRedirect();

    $post->refresh();

    expect($post->favorites_count)->toBe(1)
        ->and($post->updated_at->equalTo($originalUpdatedAt))->toBeTrue();
});
