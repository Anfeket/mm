<?php

use App\Models\Tag;
use App\Models\User;
use App\TagCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('returns empty array for empty query', function () {
    $this->getJson(route('tags.autocomplete', ['q' => '']))
        ->assertOk()
        ->assertJson([]);
});

it('returns matching tags', function () {
    Tag::factory()->general()->create(['name' => 'reaction_image', 'post_count' => 10]);
    Tag::factory()->general()->create(['name' => 'reaction_face', 'post_count' => 5]);
    Tag::factory()->general()->create(['name' => 'exploitable', 'post_count' => 3]);

    $this->getJson(route('tags.autocomplete', ['q' => 'react']))
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonFragment(['name' => 'reaction_image'])
        ->assertJsonFragment(['name' => 'reaction_face'])
        ->assertJsonMissing(['name' => 'exploitable']);
});

it('returns results ordered by post_count descending', function () {
    Tag::factory()->general()->create(['name' => 'reaction_face', 'post_count' => 5]);
    Tag::factory()->general()->create(['name' => 'reaction_image', 'post_count' => 10]);

    $response = $this->getJson(route('tags.autocomplete', ['q' => 'react']))
        ->assertOk();

    expect($response->json('0.name'))->toBe('reaction_image')
        ->and($response->json('1.name'))->toBe('reaction_face');
});

it('filters by category when prefix given', function () {
    Tag::factory()->create(['name' => 'john_doe', 'category' => TagCategory::Artist]);
    Tag::factory()->create(['name' => 'john_wick', 'category' => TagCategory::General]);

    $this->getJson(route('tags.autocomplete', ['q' => 'a:john']))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['name' => 'john_doe', 'category' => 'artist'])
        ->assertJsonMissing(['name' => 'john_wick']);
});

it('searches all categories without prefix', function () {
    Tag::factory()->create(['name' => 'john_doe', 'category' => TagCategory::Artist]);
    Tag::factory()->create(['name' => 'john_wick', 'category' => TagCategory::General]);

    $this->getJson(route('tags.autocomplete', ['q' => 'john']))
        ->assertOk()
        ->assertJsonCount(2);
});

it('includes alias information', function () {
    $canonical = Tag::factory()->general()->create(['name' => 'reaction_image']);
    Tag::factory()->general()->create([
        'name'        => 'reaction_pic',
        'alias_tag_id' => $canonical->id,
    ]);

    $response = $this->getJson(route('tags.autocomplete', ['q' => 'reaction_pic']))
        ->assertOk();

    expect($response->json('0.alias_of'))->toBe('reaction_image');
});

it('returns null alias_of for non aliased tags', function () {
    Tag::factory()->general()->create(['name' => 'reaction_image']);

    $response = $this->getJson(route('tags.autocomplete', ['q' => 'reaction']))
        ->assertOk();

    expect($response->json('0.alias_of'))->toBeNull();
});

it('respects the limit', function () {
    Tag::factory()->general()->createMany(
        collect(range(1, 15))->map(fn($i) => ['name' => "tag_{$i}"])->all()
    );

    $this->getJson(route('tags.autocomplete', ['q' => 'tag']))
        ->assertOk()
        ->assertJsonCount(10); // default limit
});
