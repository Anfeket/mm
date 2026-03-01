<?php

use App\Models\Tag;
use App\Models\Post;
use App\Models\User;
use App\Services\TagService;
use App\TagCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new TagService();
    $this->user    = User::factory()->create();

    $this->actingAs($this->user);
});

// -------------------------------------------------------------------------
// resolveAlias
// -------------------------------------------------------------------------

describe('resolveAlias', function () {

    it('returns the tag itself when it has no alias', function () {
        $tag = Tag::factory()->create();

        expect($this->service->resolveAlias($tag)->id)->toBe($tag->id);
    });

    it('follows a single alias to the canonical tag', function () {
        $canonical = Tag::factory()->create();
        $alias     = Tag::factory()->create(['alias_tag_id' => $canonical->id]);

        expect($this->service->resolveAlias($alias)->id)->toBe($canonical->id);
    });

    it('follows a chain of aliases to the canonical tag', function () {
        $canonical = Tag::factory()->create();
        $middle    = Tag::factory()->create(['alias_tag_id' => $canonical->id]);
        $alias     = Tag::factory()->create(['alias_tag_id' => $middle->id]);

        expect($this->service->resolveAlias($alias)->id)->toBe($canonical->id);
    });

    it('throws on a circular alias chain', function () {
        $tagA = Tag::factory()->create();
        $tagB = Tag::factory()->create(['alias_tag_id' => $tagA->id]);

        // Force circular reference directly in DB, bypassing validateNoCircularAlias
        $tagA->alias_tag_id = $tagB->id;
        $tagA->saveQuietly();
        $tagA->refresh();

        expect(fn () => $this->service->resolveAlias($tagA))
            ->toThrow(\RuntimeException::class, 'Circular alias detected');
    });

});

// -------------------------------------------------------------------------
// validateNoCircularAlias
// -------------------------------------------------------------------------

describe('validateNoCircularAlias', function () {

    it('passes when there is no circular chain', function () {
        $canonical = Tag::factory()->create();
        $tag       = Tag::factory()->create();

        expect(fn () => $this->service->validateNoCircularAlias($tag, $canonical))
            ->not->toThrow(\RuntimeException::class);
    });

    it('throws when aliasing a tag to itself indirectly', function () {
        $tagA = Tag::factory()->create();
        $tagB = Tag::factory()->create(['alias_tag_id' => $tagA->id]);

        // Trying to alias A → B would make A → B → A
        expect(fn () => $this->service->validateNoCircularAlias($tagA, $tagB))
            ->toThrow(\RuntimeException::class, 'circular chain');
    });

    it('throws when an existing circular chain is detected in target chain', function () {
        $tagA = Tag::factory()->create();
        $tagB = Tag::factory()->create(['alias_tag_id' => $tagA->id]);

        // Force A → B directly to create existing circular data
        $tagA->alias_tag_id = $tagB->id;
        $tagA->saveQuietly();
        $tagA->refresh();

        $tagC = Tag::factory()->create();

        expect(fn () => $this->service->validateNoCircularAlias($tagC, $tagA))
            ->toThrow(\RuntimeException::class, 'Circular alias detected');
    });

});

// -------------------------------------------------------------------------
// findOrCreate
// -------------------------------------------------------------------------

describe('findOrCreate', function () {

    it('creates a new tag when it does not exist', function () {
        $tag = $this->service->findOrCreate('new_tag', TagCategory::General);

        expect($tag->name)->toBe('new_tag')
            ->and($tag->category)->toBe(TagCategory::General);

        $this->assertDatabaseHas('tags', ['name' => 'new_tag']);
    });

    it('returns an existing tag without creating a duplicate', function () {
        $existing = Tag::factory()->create(['name' => 'existing_tag']);

        $tag = $this->service->findOrCreate('existing_tag', TagCategory::General);

        expect($tag->id)->toBe($existing->id);
        expect(Tag::where('name', 'existing_tag')->count())->toBe(1);
    });

    it('normalizes the name before looking up or creating', function () {
        $tag = $this->service->findOrCreate('Hello World', TagCategory::General);

        expect($tag->name)->toBe('hello_world');
        $this->assertDatabaseHas('tags', ['name' => 'hello_world']);
    });

    it('resolves alias and returns canonical tag', function () {
        $canonical = Tag::factory()->create(['name' => 'canonical']);
        Tag::factory()->create(['name' => 'alias_tag', 'alias_tag_id' => $canonical->id]);

        $tag = $this->service->findOrCreate('alias_tag', TagCategory::General);

        expect($tag->id)->toBe($canonical->id);
    });

    it('stores the authenticated user as creator', function () {
        $this->service->findOrCreate('authored_tag', TagCategory::General);

        $this->assertDatabaseHas('tags', [
            'name'       => 'authored_tag',
            'created_by' => $this->user->id,
        ]);
    });

});

// -------------------------------------------------------------------------
// syncPostTags
// -------------------------------------------------------------------------

describe('syncPostTags', function () {

    beforeEach(function () {
        $this->post = Post::factory()->create(['author_id' => $this->user->id]);
    });

    it('attaches a tag to a post', function () {
        $this->service->syncPostTags($this->post, [
            ['name' => 'reaction_image', 'category' => TagCategory::General],
        ]);

        expect($this->post->tags()->where('name', 'reaction_image')->exists())->toBeTrue();
    });

    it('creates the tag if it does not exist', function () {
        $this->service->syncPostTags($this->post, [
            ['name' => 'brand_new_tag', 'category' => TagCategory::General],
        ]);

        $this->assertDatabaseHas('tags', ['name' => 'brand_new_tag']);
    });

    it('increments post_count on the tag', function () {
        $this->service->syncPostTags($this->post, [
            ['name' => 'counted_tag', 'category' => TagCategory::General],
        ]);

        expect(Tag::where('name', 'counted_tag')->first()->post_count)->toBe(1);
    });

    it('does not attach the same tag twice', function () {
        $input = [['name' => 'reaction_image', 'category' => TagCategory::General]];

        $this->service->syncPostTags($this->post, $input);
        $this->service->syncPostTags($this->post, $input);

        expect($this->post->tags()->where('name', 'reaction_image')->count())->toBe(1);
    });

    it('does not double-increment post_count on duplicate sync', function () {
        $input = [['name' => 'reaction_image', 'category' => TagCategory::General]];

        $this->service->syncPostTags($this->post, $input);
        $this->service->syncPostTags($this->post, $input);

        expect(Tag::where('name', 'reaction_image')->first()->post_count)->toBe(1);
    });

    it('attaches multiple tags in one call', function () {
        $this->service->syncPostTags($this->post, [
            ['name' => 'tag_one', 'category' => TagCategory::General],
            ['name' => 'tag_two', 'category' => TagCategory::General],
            ['name' => 'tag_three', 'category' => TagCategory::Subject],
        ]);

        expect($this->post->tags()->count())->toBe(3);
    });

    it('stores the authenticated user in the pivot', function () {
        $this->service->syncPostTags($this->post, [
            ['name' => 'pivot_tag', 'category' => TagCategory::General],
        ]);

        $tag = Tag::where('name', 'pivot_tag')->first();

        $this->assertDatabaseHas('post_tags', [
            'post_id'         => $this->post->id,
            'tag_id'          => $tag->id,
            'added_by_user_id' => $this->user->id,
        ]);
    });

    it('resolves alias before attaching', function () {
        $canonical = Tag::factory()->create(['name' => 'canonical_tag']);
        Tag::factory()->create(['name' => 'alias_tag', 'alias_tag_id' => $canonical->id]);

        $this->service->syncPostTags($this->post, [
            ['name' => 'alias_tag', 'category' => TagCategory::General],
        ]);

        // Should be attached under the canonical tag, not the alias
        expect($this->post->tags()->where('tag_id', $canonical->id)->exists())->toBeTrue();
    });

});
