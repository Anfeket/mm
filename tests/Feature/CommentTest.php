<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->post = Post::factory()->create();
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

it('lets an authenticated user post a comment', function () {
    $this->actingAs($this->user)
        ->post(route('posts.comments.store', $this->post), ['content' => 'Nice post!'])
        ->assertRedirect(route('posts.show', $this->post));

    expect(Comment::count())->toBe(1)
        ->and(Comment::first()->content)->toBe('Nice post!')
        ->and(Comment::first()->user_id)->toBe($this->user->id)
        ->and(Comment::first()->post_id)->toBe($this->post->id);
});

it('rejects unauthenticated comment submissions', function () {
    $this->post(route('posts.comments.store', $this->post), ['content' => 'Hello'])
        ->assertRedirect(route('login'));

    expect(Comment::count())->toBe(0);
});

it('rejects an empty comment', function () {
    $this->actingAs($this->user)
        ->post(route('posts.comments.store', $this->post), ['content' => ''])
        ->assertSessionHasErrors('content');

    expect(Comment::count())->toBe(0);
});

it('rejects a whitespace-only comment', function () {
    $this->actingAs($this->user)
        ->post(route('posts.comments.store', $this->post), ['content' => "   \t\n  "])
        ->assertSessionHasErrors('content');

    expect(Comment::count())->toBe(0);
});

it('trims surrounding whitespace before storing', function () {
    $this->actingAs($this->user)
        ->post(route('posts.comments.store', $this->post), ['content' => '  hello  '])
        ->assertRedirect();

    expect(Comment::first()->content)->toBe('hello');
});

it('rejects a comment that exceeds 2000 characters', function () {
    $this->actingAs($this->user)
        ->post(route('posts.comments.store', $this->post), ['content' => str_repeat('a', 2001)])
        ->assertSessionHasErrors('content');

    expect(Comment::count())->toBe(0);
});

it('accepts a comment that is exactly 2000 characters', function () {
    $this->actingAs($this->user)
        ->post(route('posts.comments.store', $this->post), ['content' => str_repeat('a', 2000)])
        ->assertRedirect();

    expect(Comment::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

it('lets the comment author delete their own comment', function () {
    $comment = Comment::factory()->create([
        'post_id' => $this->post->id,
        'user_id' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->delete(route('comments.destroy', $comment))
        ->assertRedirect(route('posts.show', $this->post));

    expect(Comment::count())->toBe(0);
});

it('prevents a user from deleting another user\'s comment', function () {
    $other = User::factory()->create();
    $comment = Comment::factory()->create([
        'post_id' => $this->post->id,
        'user_id' => $other->id,
    ]);

    $this->actingAs($this->user)
        ->delete(route('comments.destroy', $comment))
        ->assertForbidden();

    expect(Comment::count())->toBe(1);
});

it('rejects unauthenticated delete attempts', function () {
    $comment = Comment::factory()->create([
        'post_id' => $this->post->id,
        'user_id' => $this->user->id,
    ]);

    $this->delete(route('comments.destroy', $comment))
        ->assertRedirect(route('login'));

    expect(Comment::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// rate limiting
// ---------------------------------------------------------------------------

it('rate limits excessive comment submissions', function () {
    // 10 allowed per minute — the 11th should be throttled
    foreach (range(1, 10) as $i) {
        $this->actingAs($this->user)
            ->post(route('posts.comments.store', $this->post), ['content' => "comment {$i}"])
            ->assertRedirect();
    }

    $this->actingAs($this->user)
        ->post(route('posts.comments.store', $this->post), ['content' => 'one too many'])
        ->assertStatus(429);
});
