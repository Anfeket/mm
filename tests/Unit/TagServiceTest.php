<?php

use App\Services\TagService;
use App\TagCategory;

beforeEach(function () {
    $this->service = new TagService();
});

// -------------------------------------------------------------------------
// normalizeTagName
// -------------------------------------------------------------------------

describe('normalizeTagName', function () {

    it('lowercases the input', function () {
        expect($this->service->normalizeTagName('Hello'))->toBe('hello');
    });

    it('converts spaces to underscores', function () {
        expect($this->service->normalizeTagName('hello world'))->toBe('hello_world');
    });

    it('collapses multiple spaces into one underscore', function () {
        expect($this->service->normalizeTagName('hello   world'))->toBe('hello_world');
    });

    it('collapses multiple underscores', function () {
        expect($this->service->normalizeTagName('hello__world'))->toBe('hello_world');
    });

    it('strips leading and trailing underscores', function () {
        expect($this->service->normalizeTagName('_hello_'))->toBe('hello');
    });

    it('allows hyphens', function () {
        expect($this->service->normalizeTagName('spider-man'))->toBe('spider-man');
    });

    it('allows parentheses', function () {
        expect($this->service->normalizeTagName('batman_(1989)'))->toBe('batman_(1989)');
    });

    it('allows colons', function () {
        expect($this->service->normalizeTagName('re:zero'))->toBe('re:zero');
    });

    it('allows numbers', function () {
        expect($this->service->normalizeTagName('tag123'))->toBe('tag123');
    });

    it('strips disallowed special characters', function () {
        expect($this->service->normalizeTagName('tag!@#$%^&*'))->toBe('tag');
    });

    it('handles mixed case with spaces and special chars', function () {
        expect($this->service->normalizeTagName('  Hello World!  '))->toBe('hello_world');
    });
});

// -------------------------------------------------------------------------
// parseInput
// -------------------------------------------------------------------------

describe('parseInput', function () {

    it('returns empty array for empty string', function () {
        expect($this->service->parseInput(''))->toBe([]);
    });

    it('returns empty array for whitespace only', function () {
        expect($this->service->parseInput('   '))->toBe([]);
    });

    it('parses plain tag as General', function () {
        $tags = $this->service->parseInput('reaction_image');

        expect($tags)->toHaveCount(1)
            ->and($tags[0]['name'])->toBe('reaction_image')
            ->and($tags[0]['category'])->toBe(TagCategory::General);
    });

    it('parses multiple plain tags', function () {
        $tags = $this->service->parseInput('reaction_image exploitable');

        expect($tags)->toHaveCount(2)
            ->and($tags[0]['name'])->toBe('reaction_image')
            ->and($tags[1]['name'])->toBe('exploitable');
    });

    it('parses short prefix a: as Artist', function () {
        $tags = $this->service->parseInput('a:john_doe');

        expect($tags)->toHaveCount(1)
            ->and($tags[0]['name'])->toBe('john_doe')
            ->and($tags[0]['category'])->toBe(TagCategory::Artist);
    });

    it('parses long prefix artist: as Artist', function () {
        $tags = $this->service->parseInput('artist:john_doe');

        expect($tags)->toHaveCount(1)
            ->and($tags[0]['name'])->toBe('john_doe')
            ->and($tags[0]['category'])->toBe(TagCategory::Artist);
    });

    it('parses all recognised short prefixes', function () {
        $cases = [
            'a'  => TagCategory::Artist,
            'c'  => TagCategory::Copyright,
            'o'  => TagCategory::Origin,
            'f'  => TagCategory::Format,
            't'  => TagCategory::Template,
            'g'  => TagCategory::General,
            'u'  => TagCategory::Usage,
            'm'  => TagCategory::Meta,
            's'  => TagCategory::Subject,
        ];

        foreach ($cases as $prefix => $expected) {
            $tags = $this->service->parseInput("{$prefix}:test");
            expect($tags[0]['category'])->toBe($expected, "Prefix '{$prefix}:' should map to {$expected->value}");
        }
    });

    it('treats unrecognised prefix as part of tag name and uses General', function () {
        $tags = $this->service->parseInput('re:zero');

        expect($tags)->toHaveCount(1)
            ->and($tags[0]['name'])->toBe('re:zero')
            ->and($tags[0]['category'])->toBe(TagCategory::General);
    });

    it('uses provided default category for plain tags', function () {
        $tags = $this->service->parseInput('john_doe', TagCategory::Artist);

        expect($tags[0]['category'])->toBe(TagCategory::Artist);
    });

    it('explicit prefix overrides default category', function () {
        // even if default is Artist, an explicit c: prefix should win
        $tags = $this->service->parseInput('c:some_series', TagCategory::Artist);

        expect($tags[0]['category'])->toBe(TagCategory::Copyright);
    });

    it('normalizes each token in input', function () {
        $tags = $this->service->parseInput('Hello WORLD');

        expect($tags)->toHaveCount(2)
            ->and($tags[0]['name'])->toBe('hello')
            ->and($tags[1]['name'])->toBe('world');
    });

    it('skips tokens that normalize to empty string', function () {
        // only special chars that get stripped
        $tags = $this->service->parseInput('!!! @@@');

        expect($tags)->toHaveCount(0);
    });

    it('parses mixed prefixed and plain tags', function () {
        $tags = $this->service->parseInput('a:john_doe reaction_image s:gaming');

        expect($tags)->toHaveCount(3)
            ->and($tags[0])->toMatchArray(['name' => 'john_doe',       'category' => TagCategory::Artist])
            ->and($tags[1])->toMatchArray(['name' => 'reaction_image', 'category' => TagCategory::General])
            ->and($tags[2])->toMatchArray(['name' => 'gaming',         'category' => TagCategory::Subject]);
    });
});
