<?php

namespace App\Discord\Commands;

use App\Discord\DiscordCommand;
use App\Discord\Interaction;
use App\Discord\InteractionResponse;

class PingCommand implements DiscordCommand
{
    public static function definition(): array
    {
        return [
            'name' => 'ping',
            'description' => 'Check if the bot is alive.',
        ];
    }

    public function __invoke(Interaction $interaction): InteractionResponse
    {
        return InteractionResponse::message()->content('Pong!')->ephemeral();
    }
}
