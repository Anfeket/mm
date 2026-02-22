<?php

namespace App;

enum TagCategory: string
{
    case Artist = 'artist';
    case Copyright = 'copyright';
    case Origin = 'origin';
    case Format = 'format';
    case Template = 'template';
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
            self::General => 'General',
            self::Usage => 'Usage',
            self::Meta => 'Meta',
        };
    }
}
