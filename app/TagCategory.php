<?php

namespace App;

enum TagCategory: string
{
    case Artist = 'artist';
    case Copyright = 'copyright';
    case Origin = 'origin';
    case Template = 'template';
    case General = 'general';
    case Meta = 'meta';
    case Language = 'language';

    public function label(): string
    {
        return match ($this) {
            self::Artist => 'Artist',
            self::Copyright => 'Copyright',
            self::Origin => 'Origin',
            self::Template => 'Template',
            self::General => 'General',
            self::Meta => 'Meta',
            self::Language => 'Language',
        };
    }

    public function prefixes(): array
    {
        return match ($this) {
            self::Artist => ['a', 'artist'],
            self::Copyright => ['c', 'copyright'],
            self::Origin => ['o', 'origin'],
            self::Template => ['t', 'template'],
            self::General => ['g', 'general'],
            self::Meta => ['m', 'meta'],
            self::Language => ['l', 'language'],
        };
    }

    public static function fromPrefix(string $prefix): ?self
    {
        foreach (self::cases() as $category) {
            if (in_array($prefix, $category->prefixes(), true)) {
                return $category;
            }
        }

        return null;
    }
}
