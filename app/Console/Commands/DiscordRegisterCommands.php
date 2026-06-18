<?php

namespace App\Console\Commands;

use App\Http\Controllers\DiscordInteractionController;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('discord:register-commands')]
#[Description('Register slash commands with Discord.')]
class DiscordRegisterCommands extends Command
{
    public function handle(): int
    {
        $appId = config('services.discord.app_id');
        $token = config('services.discord.bot_token');

        if (! $appId || ! $token) {
            $this->error('DISCORD_APP_ID and DISCORD_BOT_TOKEN must be set.');

            return self::FAILURE;
        }

        $commands = app(DiscordInteractionController::class)->getCommands();

        $response = Http::withToken($token, 'Bot')
            ->put("https://discord.com/api/v10/applications/{$appId}/commands", $commands);

        if ($response->failed()) {
            $this->error('Failed to register commands: '.$response->body());

            return self::FAILURE;
        }

        $registered = collect($response->json())->pluck('name');
        $this->info('Registered commands: '.$registered->join(', '));

        return self::SUCCESS;
    }
}
