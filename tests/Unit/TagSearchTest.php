<?php

use App\Services\TagService;
use App\TagCategory;

describe('parseSearchInput', function () {
    beforeEach(function () {
        $this->service = new TagService();
    });

    it('returns empty arrays for empty input', function () {
        $result = $this->service->parseSearchInput('');
        expect($result['include'])->toBe([])
            ->and($result['exclude'])->toBe([]);
    });

    it('parses basic inclusion', function () {
        $result = $this->service->parseSearchInput('cat dog');
        expect($result['include'])->toHaveCount(2)
            ->and($result['exclude'])->toBe([]);
        expect($result['include'][0]['name'])->toBe('cat');
        expect($result['include'][1]['name'])->toBe('dog');
    });

    it('parses exclusion with negation', function () {
        $result = $this->service->parseSearchInput('-cat dog');
        expect($result['include'])->toHaveCount(1)
            ->and($result['exclude'])->toHaveCount(1);
        expect($result['include'][0]['name'])->toBe('dog');
        expect($result['exclude'][0]['name'])->toBe('cat');
    });

    it('parses multiple exclusions', function () {
        $result = $this->service->parseSearchInput('-cat -dog');
        expect($result['include'])->toBe([])
            ->and($result['exclude'])->toHaveCount(2);
        expect($result['exclude'][0]['name'])->toBe('cat');
        expect($result['exclude'][1]['name'])->toBe('dog');
    });

    it('parses mix of include and exclude', function () {
        $result = $this->service->parseSearchInput('cat -dog bird');
        expect($result['include'])->toHaveCount(2)
            ->and($result['exclude'])->toHaveCount(1);
        expect($result['include'][0]['name'])->toBe('cat');
        expect($result['include'][1]['name'])->toBe('bird');
        expect($result['exclude'][0]['name'])->toBe('dog');
    });

    it('parses category prefixes', function () {
        $result = $this->service->parseSearchInput('a:john_doe -c:disney');
        expect($result['include'])->toHaveCount(1)
            ->and($result['exclude'])->toHaveCount(1);
        expect($result['include'][0]['name'])->toBe('john_doe')
            ->and($result['include'][0]['category'])->toBe(TagCategory::Artist);
        expect($result['exclude'][0]['name'])->toBe('disney')
            ->and($result['exclude'][0]['category'])->toBe(TagCategory::Copyright);
    });

    it('parses negation with prefix', function () {
        $result = $this->service->parseSearchInput('-a:john_doe');
        expect($result['include'])->toBe([])
            ->and($result['exclude'])->toHaveCount(1);
        expect($result['exclude'][0]['name'])->toBe('john_doe')
            ->and($result['exclude'][0]['category'])->toBe(TagCategory::Artist);
    });

    it('handles duplicate tags (negate after include)', function () {
        $result = $this->service->parseSearchInput('cat -cat');
        expect($result['include'])->toBe([])
            ->and($result['exclude'])->toHaveCount(1);
        expect($result['exclude'][0]['name'])->toBe('cat');
    });

    it('handles duplicate tags (include after negate)', function () {
        $result = $this->service->parseSearchInput('-cat cat');
        expect($result['include'])->toBe([])
            ->and($result['exclude'])->toHaveCount(1);
        expect($result['exclude'][0]['name'])->toBe('cat');
    });

    it('skips tokens that normalize to empty string', function () {
        $result = $this->service->parseSearchInput('!!! -@@@');
        expect($result['include'])->toBe([])
            ->and($result['exclude'])->toBe([]);
    });

    it('treats unrecognised prefix as tag name with general category', function () {
        $result = $this->service->parseSearchInput('re:zero');
        expect($result['include'])->toHaveCount(1)
            ->and($result['include'][0]['name'])->toBe('re:zero')
            ->and($result['include'][0]['category'])->toBe(TagCategory::General);
    });

    it('keeps same name with different categories as separate entries', function () {
        $result = $this->service->parseSearchInput('g:cat s:cat');
        expect($result['include'])->toHaveCount(2);
    });
});
