<?php

namespace App;

enum TagCategory: string
{
    case Artist = 'artist';
    case Copyright = 'copyright';
    case Origin = 'origin';
    case Format = 'format';
    case Template = 'template';
    case Subject = 'subject';
    case General = 'general';
    case Usage = 'usage';
    case Meta = 'meta';

    public function label(): string
    {
        return match ($this) {
            self::Artist => 'Artist',
            self::Copyright => 'Copyright',
            self::Origin => 'Origin',
            self::Format => 'Format',
            self::Template => 'Template',
            self::Subject => 'Subject',
            self::General => 'General',
            self::Usage => 'Usage',
            self::Meta => 'Meta',
        };
    }

    public function prefixes(): array
    {
        return match ($this) {
            self::Artist => ['a', 'artist'],
            self::Copyright => ['c', 'copyright'],
            self::Origin => ['o', 'origin'],
            self::Format => ['f', 'format'],
            self::Template => ['t', 'template'],
            self::Subject => ['s', 'subject'],
            self::General => ['g', 'general'],
            self::Usage => ['u', 'usage'],
            self::Meta => ['m', 'meta'],
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
