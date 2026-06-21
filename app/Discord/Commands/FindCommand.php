<?php

namespace App\Discord\Commands;

use App\Discord\DiscordCommand;
use App\Discord\Embed;
use App\Discord\Interaction;
use App\Discord\InteractionResponse;
use App\Models\Post;
use App\Services\TagService;

class FindCommand implements DiscordCommand
{
    public static function definition(): array
    {
        return [
            'name' => 'find',
            'description' => 'Find a post',
            'options' => [
                [
                    'type' => 1,
                    'name' => 'id',
                    'description' => 'Find a post by ID',
                    'options' => [
                        [
                            'type' => 4,
                            'name' => 'id',
                            'description' => 'Post ID',
                            'required' => true,
                        ],
                    ],
                ],
                [
                    'type' => 1,
                    'name' => 'tags',
                    'description' => 'Find a post by tags',
                    'options' => [
                        [
                            'type' => 3,
                            'name' => 'tags',
                            'description' => 'Tags to search for',
                            'required' => true,
                        ],
                    ],
                ]
            ]
        ];
    }

    public function __invoke(Interaction $interaction): InteractionResponse
    {
        return match ($interaction->subcommand()) {
            'id' => $this->findById($interaction),
            'tags' => $this->findByTags($interaction, app(TagService::class)),
            default => InteractionResponse::message()->content('Unknown subcommand.')->ephemeral(),
        };
    }

    private function findById(Interaction $interaction): InteractionResponse
    {
        $postId = $interaction->subOption('id');
        $post = Post::where('is_listed', true)
            ->with('author')
            ->find($postId);

        if (!$post) {
            return InteractionResponse::message()->content('Post not found.')->ephemeral();
        }

        // Discord video bypass
        if ($post->isVideo()) {
            return InteractionResponse::message()
                ->content(route('posts.show', $post));
        }

        $embed = (new Embed)
            ->title("Post #{$post->id}")
            ->url(route('posts.show', $post))
            ->footer("Uploaded by {$post->author->username}")
            ->timestamp($post->created_at);

        if ($post->thumb_path && $post->isImage()) {
            $embed->image(asset('uploads/' . $post->thumb_path));
        }

        if ($post->description) {
            $embed->description($post->description);
        }

        return InteractionResponse::message()->embed($embed);
    }

    private function findByTags(Interaction $interaction, TagService $tagService): InteractionResponse
    {
        $tags = $tagService->parseSearchInput($interaction->subOption('tags'));

        $query = Post::query()->where('is_listed', true);

        foreach ($tags['include'] as $tag) {
            $query->whereHas(
                'tags',
                fn($q) => $q
                    ->where('name', $tag['name'])
                    ->where('category', $tag['category'])
            );
        }
        foreach ($tags['exclude'] as $tag) {
            $query->whereDoesntHave(
                'tags',
                fn($q) => $q
                    ->where('name', $tag['name'])
                    ->where('category', $tag['category'])
            );
        }

        $post = $query->with('author')->first();

        if (!$post) {
            return InteractionResponse::message()->content('No posts found with the given tags.')->ephemeral();
        }

        if ($post->isVideo()) {
            return InteractionResponse::message()
                ->content(route('posts.show', $post));
        }

        $embed = (new Embed)
            ->title("Post #{$post->id}")
            ->url(route('posts.show', $post))
            ->footer("Uploaded by {$post->author->username}")
            ->timestamp($post->created_at);

        if ($post->thumb_path && $post->isImage()) {
            $embed->image(asset('uploads/' . $post->thumb_path));
        }

        if ($post->description) {
            $embed->description($post->description);
        }

        return InteractionResponse::message()->embed($embed);
    }
}
