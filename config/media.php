<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media Hash Algorithm
    |--------------------------------------------------------------------------
    |
    | This option controls the hashing algorithm used for media files. You can
    | specify any algorithm supported by PHP's hash_algos function, such as
    | 'md5', 'sha1', 'sha256', etc.
    |
     */

    'hash' => env('MEDIA_HASH_ALGO', 'md5'),

    /*
    |--------------------------------------------------------------------------
    | ffmpeg Configuration
    |--------------------------------------------------------------------------
    |
    | These options configure the ffmpeg binary used for video processing.
    |
    | "default_args" are prepended to every ffmpeg and ffprobe command.
    |
    | "url" is the download URL for the static ffmpeg build, used by the
    | ffmpeg:install artisan command. Defaults to the latest Linux x86-64
    | GPL build from BtbN/FFmpeg-Builds. Override via FFMPEG_URL in .env
    | if you need a different architecture or license variant.
    |
    */

    'ffmpeg' => [
        'default_args' => [
            '-hide_banner',
            '-loglevel error',
        ],
        'url' => env('FFMPEG_URL', 'https://github.com/BtbN/FFmpeg-Builds/releases/latest/download/ffmpeg-master-latest-linux64-gpl.tar.xz'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Configuration
    |--------------------------------------------------------------------------
    |
    | This section defines the settings for generating thumbnails for media files.
    | You can specify the width and height for the thumbnails, as well as the
    | quality level for image thumbnails (1-100). For video thumbnails, you can
    | specify the timestamp (in seconds) at which to capture the thumbnail frame.
    |
     */

    'thumb' => [
        'width' => 300,
        'height' => 300,
        'quality' => 80, // For image thumbnails (1-100)
        'video_frame_time' => 1, // Time in seconds to capture video thumbnail
    ],

    /*
    |--------------------------------------------------------------------------
    | Avatar Configuration
    |--------------------------------------------------------------------------
    |
    | This section defines the settings for user avatar images. You can specify
    | the size (in pixels) for the avatar images, as well as the quality level
    | for the generated avatar thumbnails (1-100).
    |
     */

    'avatar' => [
        'size' => 128,
        'quality' => 80,
    ],
];
