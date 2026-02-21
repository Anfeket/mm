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
    | ffmpeg URL
    |--------------------------------------------------------------------------
    |
    | This option specifies the URL from which to download ffmpeg for video
    | processing. You can set this to a custom URL if you have a specific build
    | of ffmpeg you want to use, or leave it as the default to use the standard
    | builds from btbN builds.
    |
     */

    'ffmpeg_url' => env('FFMPEG_URL', 'https://github.com/BtbN/FFmpeg-Builds/releases/latest/download/ffmpeg-master-latest-linux64-gpl.tar.xz'),

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
];
