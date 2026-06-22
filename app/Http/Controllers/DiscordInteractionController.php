<?php

namespace App\Http\Controllers;

use App\Discord\Commands\FindCommand;
use App\Discord\Commands\PingCommand;
use App\Discord\HandlesAutocomplete;
use App\Discord\Interaction;
use App\Discord\InteractionResponse;
use App\Discord\InteractionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DiscordInteractionController extends Controller
{
    private array $commands = [
        'ping' => PingCommand::class,
        'find' => FindCommand::class,
    ];

    public function handle(Request $request)
    {
        $interaction = new Interaction($request->all());

        if ($interaction->type() === InteractionType::Ping) {
            return InteractionResponse::pong()->toResponse();
        }

        $name = $request->input('data.name');
        $handler = $this->commands[$name] ?? null;

        if (! $handler) {
            return InteractionResponse::message()
                ->content("Unknown command: $name")
                ->ephemeral()
                ->toResponse();
        }

        Log::debug('Discord headers', [
            'signature' => $request->header('X-Signature-Ed25519'),
            'timestamp' => $request->header('X-Signature-Timestamp'),
        ]);

        if ($interaction->type() === InteractionType::ApplicationCommandAutocomplete) {
            if ($handler && is_a($handler, HandlesAutocomplete::class, true)) {
                return app($handler)->autocomplete($interaction)->toResponse();
            }

            return InteractionResponse::message()
                ->content("Command $name does not support autocomplete.")
                ->ephemeral()
                ->toResponse();
        }

        return app($handler)($interaction)->toResponse();
    }

    public function getCommands(): array
    {
        return array_values(array_map(fn ($command) => $command::definition(), $this->commands));
    }
}
