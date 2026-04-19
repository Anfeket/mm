<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns true and url when hash exists', function () {
    $user = User::factory()->create();
    $hash = '12345678901234567890123456789012';

    $post = Post::factory()->create([
        'file_hash' => $hash,
    ]);

    $this->actingAs($user)
        ->getJson("/upload/check-hash?hash={$hash}")
        ->assertOk()
        ->assertJson([
            'exists' => true,
            'url' => route('posts.show', $post)
        ]);
});

it('returns false when hash does not exist', function () {
    $user = User::factory()->create();
    $hash = '12345678901234567890123456789012';

    $this->actingAs($user)
        ->getJson("/upload/check-hash?hash={$hash}")
        ->assertOk()
        ->assertJson([
            'exists' => false
        ]);
});

it('returns validation error when hash is missing', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/upload/check-hash')
        ->assertInvalid(['hash']);
});

it('returns validation error when hash is too short', function () {
    $user = User::factory()->create();
    $shortHash = '12345';

    $this->actingAs($user)
        ->getJson("/upload/check-hash?hash={$shortHash}")
        ->assertInvalid(['hash']);
});

it('requires authentication to check hashes', function () {
    $hash = '12345678901234567890123456789012';

    $this->getJson("/upload/check-hash?hash={$hash}")
        ->assertUnauthorized();
});

it('returns validation error when hash contains non-hex characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/upload/check-hash?hash=' . str_repeat('z', 32))
        ->assertInvalid(['hash']);
});
