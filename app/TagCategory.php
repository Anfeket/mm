<?php

namespace App;

enum TagCategory: string
{
    case General = 'general';
    case Artist = 'artist';
    case Copyright = 'copyright';
    case Meta = 'meta';
}
