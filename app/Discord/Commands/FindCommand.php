<?php

namespace App\Discord\Commands;

use App\Discord\DiscordCommand;
use App\Discord\Embed;
use App\Discord\Interaction;
use App\Discord\InteractionResponse;
use App\Models\Post;

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
            ]
        ];
    }

    public function __invoke(Interaction $interaction): InteractionResponse
    {
        return match ($interaction->subcommand()) {
            'id' => $this->findById($interaction),
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

        $embed = (new Embed)
            ->title($post->title)
            ->url(route('posts.show', $post))
            ->footer("Uploaded by {$post->author->username}")
            ->timestamp($post->created_at);

        if ($post->thumb_path) {
            $embed->image(asset('uploads/' . $post->thumb_path));
        }

        if ($post->description) {
            $embed->description($post->description);
        }

        return InteractionResponse::message()->embed($embed);
    }
}
