<?php

namespace App\Discord;

interface DiscordCommand
{
    public static function definition(): array;

    public function __invoke(Interaction $interaction): InteractionResponse;
}
