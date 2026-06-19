<?php

namespace App\Console\Commands;

use App\Http\Controllers\DiscordInteractionController;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

#[Signature('discord:register-commands {--guild= : Register commands for a specific guild (for testing).}')]
#[Description('Register slash commands with Discord.')]
class DiscordRegisterCommands extends Command
{
    public function handle(): int
    {
        $appId = config('services.discord.app_id');
        $token = config('services.discord.bot_token');
        $guildId = $this->option('guild');

        if (! $appId || ! $token) {
            $this->error('DISCORD_APP_ID and DISCORD_BOT_TOKEN must be set.');
            return self::FAILURE;
        }

        $commands = app(DiscordInteractionController::class)->getCommands();

        Log::debug('Registering Discord commands', ['commands' => $commands]);

        $url = $guildId
            ? "https://discord.com/api/v10/applications/{$appId}/guilds/{$guildId}/commands"
            : "https://discord.com/api/v10/applications/{$appId}/commands";

        $response = Http::withToken($token, 'Bot')
            ->put($url, $commands);

        if ($response->failed()) {
            $this->error('Failed to register commands: '.$response->body());

            return self::FAILURE;
        }

        $registered = collect($response->json())->pluck('name');
        $this->info('Registered commands: '.$registered->join(', '));

        return self::SUCCESS;
    }
}
