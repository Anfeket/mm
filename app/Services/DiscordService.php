<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Http;

class DiscordService
{
    public function __construct()
    {
        $this->webhook_url = config('services.discord.webhook_url');
    }

    protected ?string $webhook_url;

    public function sendNewPost(Post $post): void
    {
        if (! $this->webhook_url) {
            return;
        }

        $embed = $this->buildPostEmbed($post);

        Http::post($this->webhook_url, [
            'embeds' => [$embed],
        ])->throw();
    }

    public function buildPostEmbed(Post $post): array
    {
        $embed = [
            'title' => "Post #{$post->id}",
            'url' => route('posts.show', $post),
            'footer' => [
                'text' => "Uploaded by {$post->author->username}",
            ],
            'timestamp' => $post->created_at->toIso8601String(),
            'fields' => [],
        ];

        if ($post->thumb_path) {
            $embed['image'] = [
                'url' => asset('uploads/'.$post->thumb_path),
            ];
        }

        $tags = $post->tags->groupBy(fn ($t) => $t->category->label())
            ->map(function ($tags, $category) {
                $value = $tags->pluck('name')->implode(', ');
                if (strlen($value) > 1024) {
                    $value = substr($value, 0, 1021).'...';
                }

                return [
                    'name' => $category,
                    'value' => $value,
                    'inline' => false,
                ];
            })
            ->values()
            ->all();

        $embed['fields'] = array_merge($embed['fields'], $tags);

        if ($post->source_url) {
            $host = parse_url($post->source_url, PHP_URL_HOST);
            $embed['fields'][] = [
                'name' => 'Source',
                'value' => "[{$host}]({$post->source_url})",
                'inline' => false,
            ];
        }

        return $embed;
    }
}
