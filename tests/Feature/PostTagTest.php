<?php

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\TagCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->author = User::factory()->create();
    $this->other = User::factory()->create();
    $this->post = Post::factory()->create(['author_id' => $this->author->id]);
});

// ---------------------------------------------------------------------------
// Authorization — editTags policy
// ---------------------------------------------------------------------------

it('allows the post author to attach a tag', function () {
    $this->actingAs($this->author)
        ->post(route('posts.tags.attach', $this->post), ['tag' => 'funny'])
        ->assertRedirect(route('posts.show', $this->post));

    expect($this->post->tags()->count())->toBe(1);
});

it('allows an admin to attach a tag', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('posts.tags.attach', $this->post), ['tag' => 'funny'])
        ->assertRedirect(route('posts.show', $this->post));

    expect($this->post->tags()->count())->toBe(1);
});

it('allows a moderator to attach a tag', function () {
    $mod = User::factory()->create(['role' => 'moderator']);

    $this->actingAs($mod)
        ->post(route('posts.tags.attach', $this->post), ['tag' => 'funny'])
        ->assertRedirect(route('posts.show', $this->post));

    expect($this->post->tags()->count())->toBe(1);
});

it('forbids a regular user who is not the author from attaching a tag', function () {
    $this->actingAs($this->other)
        ->post(route('posts.tags.attach', $this->post), ['tag' => 'funny'])
        ->assertForbidden();

    expect($this->post->tags()->count())->toBe(0);
});

it('redirects unauthenticated attach attempts to login', function () {
    $this->post(route('posts.tags.attach', $this->post), ['tag' => 'funny'])
        ->assertRedirect(route('login'));

    expect($this->post->tags()->count())->toBe(0);
});

it('allows the post author to detach a tag', function () {
    $tag = Tag::factory()->general()->create();
    $this->post->tags()->attach($tag->id, ['added_by_user_id' => $this->author->id]);

    $this->actingAs($this->author)
        ->delete(route('posts.tags.detach', [$this->post, $tag]))
        ->assertRedirect(route('posts.show', $this->post));

    expect($this->post->tags()->count())->toBe(0);
});

it('forbids a regular user who is not the author from detaching a tag', function () {
    $tag = Tag::factory()->general()->create();
    $this->post->tags()->attach($tag->id, ['added_by_user_id' => $this->author->id]);

    $this->actingAs($this->other)
        ->delete(route('posts.tags.detach', [$this->post, $tag]))
        ->assertForbidden();

    expect($this->post->tags()->count())->toBe(1);
});

it('redirects unauthenticated detach attempts to login', function () {
    $tag = Tag::factory()->general()->create();
    $this->post->tags()->attach($tag->id, ['added_by_user_id' => $this->author->id]);

    $this->delete(route('posts.tags.detach', [$this->post, $tag]))
        ->assertRedirect(route('login'));

    expect($this->post->tags()->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// attach — tag creation / idempotency
// ---------------------------------------------------------------------------

it('creates the tag when it does not already exist', function () {
    $this->actingAs($this->author)
        ->post(route('posts.tags.attach', $this->post), ['tag' => 'brand_new_tag'])
        ->assertRedirect();

    expect(Tag::where('name', 'brand_new_tag')->where('category', TagCategory::General)->exists())->toBeTrue();
});

it('reuses an existing tag instead of creating a duplicate', function () {
    $existing = Tag::factory()->create(['name' => 'existing_tag', 'category' => TagCategory::General]);

    $this->actingAs($this->author)
        ->post(route('posts.tags.attach', $this->post), ['tag' => 'existing_tag'])
        ->assertRedirect();

    expect(Tag::where('name', 'existing_tag')->count())->toBe(1)
        ->and($this->post->tags()->first()->id)->toBe($existing->id);
});

it('does not duplicate a tag already attached to the post', function () {
    $this->actingAs($this->author)
        ->post(route('posts.tags.attach', $this->post), ['tag' => 'funny'])
        ->assertRedirect();

    // Second attach of the same tag
    $this->actingAs($this->author)
        ->post(route('posts.tags.attach', $this->post), ['tag' => 'funny'])
        ->assertRedirect();

    expect($this->post->tags()->count())->toBe(1);
});

it('increments the tag post_count when a tag is attached', function () {
    $this->actingAs($this->author)
        ->post(route('posts.tags.attach', $this->post), ['tag' => 'countable']);

    expect(Tag::where('name', 'countable')->first()->post_count)->toBe(1);
});

it('does not increment post_count when the tag is already attached', function () {
    // Attach via the route the first time (post_count becomes 1)
    $this->actingAs($this->author)
        ->post(route('posts.tags.attach', $this->post), ['tag' => 'already_on'])
        ->assertRedirect();

    $countAfterFirst = Tag::where('name', 'already_on')->first()->post_count;
    expect($countAfterFirst)->toBe(1);

    // Attempt to attach the same tag again
    $this->actingAs($this->author)
        ->post(route('posts.tags.attach', $this->post), ['tag' => 'already_on'])
        ->assertRedirect();

    expect(Tag::where('name', 'already_on')->first()->post_count)->toBe($countAfterFirst);
});

it('supports category-prefixed tags on attach', function () {
    $this->actingAs($this->author)
        ->post(route('posts.tags.attach', $this->post), ['tag' => 'a:some_artist'])
        ->assertRedirect();

    $tag = Tag::where('name', 'some_artist')->where('category', TagCategory::Artist)->first();
    expect($tag)->not->toBeNull()
        ->and($this->post->tags()->find($tag->id))->not->toBeNull();
});

it('normalizes the tag name before attaching', function () {
    $this->actingAs($this->author)
        ->post(route('posts.tags.attach', $this->post), ['tag' => 'Hello World'])
        ->assertRedirect();

    expect(Tag::where('name', 'hello_world')->exists())->toBeTrue();
});

it('returns 422 and an error for an invalid tag (JSON)', function () {
    $this->actingAs($this->author)
        ->postJson(route('posts.tags.attach', $this->post), ['tag' => '!!!'])
        ->assertStatus(422)
        ->assertJson(['error' => 'Invalid tag.']);
});

it('returns a session error for an invalid tag (form)', function () {
    $this->actingAs($this->author)
        ->post(route('posts.tags.attach', $this->post), ['tag' => '!!!'])
        ->assertSessionHasErrors('tag');
});

it('rejects an empty tag value', function () {
    $this->actingAs($this->author)
        ->post(route('posts.tags.attach', $this->post), ['tag' => ''])
        ->assertSessionHasErrors('tag');

    expect($this->post->tags()->count())->toBe(0);
});

it('rejects a tag that exceeds 255 characters', function () {
    $this->actingAs($this->author)
        ->post(route('posts.tags.attach', $this->post), ['tag' => str_repeat('a', 256)])
        ->assertSessionHasErrors('tag');
});

// ---------------------------------------------------------------------------
// attach — JSON response
// ---------------------------------------------------------------------------

it('returns 201 JSON with tag data on successful attach (JSON)', function () {
    $this->actingAs($this->author)
        ->postJson(route('posts.tags.attach', $this->post), ['tag' => 'g:cool_stuff'])
        ->assertStatus(201)
        ->assertJsonStructure(['id', 'name', 'category'])
        ->assertJson(['name' => 'cool_stuff', 'category' => 'general']);
});

// ---------------------------------------------------------------------------
// detach — post_count and idempotency
// ---------------------------------------------------------------------------

it('decrements the tag post_count when a tag is detached', function () {
    $tag = Tag::factory()->general()->create(['post_count' => 1]);
    $this->post->tags()->attach($tag->id, ['added_by_user_id' => $this->author->id]);

    $this->actingAs($this->author)
        ->delete(route('posts.tags.detach', [$this->post, $tag]));

    expect(Tag::find($tag->id)->post_count)->toBe(0);
});

it('does not decrement post_count when the tag was not attached to the post', function () {
    $tag = Tag::factory()->general()->create(['post_count' => 5]);

    $this->actingAs($this->author)
        ->delete(route('posts.tags.detach', [$this->post, $tag]));

    expect(Tag::find($tag->id)->post_count)->toBe(5);
});

// ---------------------------------------------------------------------------
// detach — JSON response
// ---------------------------------------------------------------------------

it('returns 204 No Content on successful detach (JSON)', function () {
    $tag = Tag::factory()->general()->create();
    $this->post->tags()->attach($tag->id, ['added_by_user_id' => $this->author->id]);

    $this->actingAs($this->author)
        ->deleteJson(route('posts.tags.detach', [$this->post, $tag]))
        ->assertNoContent();
});
