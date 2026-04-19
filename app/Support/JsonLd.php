<?php

namespace App\Support;

use App\Models\Post;

class JsonLd
{
    public static function forPost(Post $post): array
    {
        $type = $post->isVideo() ? 'VideoObject' : 'ImageObject';

        $data = [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => "Post #{$post->id}",
            'url' => route('posts.show', $post),
            'uploadDate' => $post->created_at->toISO8601String(),
            'contentUrl' => asset('uploads/'.$post->file_path),
            'contentSize' => $post->file_size,
            'encodingFormat' => $post->mime_type,
        ];

        if ($post->thumb_path) {
            $data['thumbnailUrl'] = asset('uploads/'.$post->thumb_path);
        }

        if ($post->description) {
            $data['description'] = $post->description;
        }

        if ($post->tags->isNotEmpty()) {
            $data['keywords'] = $post->tags->pluck('name')->implode(', ');
        }

        if ($post->isVideo() && $post->duration_ms) {
            $seconds = intdiv($post->duration_ms, 1000);
            $minutes = intdiv($seconds, 60);
            $seconds = $seconds % 60;
            $data['duration'] = "PT{$minutes}M{$seconds}S";
        }

        return $data;
    }

    public static function forSite(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('app.name'),
            'url' => url('/'),
            'description' => config('app.description'),
        ];
    }
}
