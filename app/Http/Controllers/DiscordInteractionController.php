<?php

namespace App\Http\Controllers;

use App\Discord\Commands\PingCommand;
use App\Discord\Interaction;
use App\Discord\InteractionResponse;
use App\Discord\InteractionType;
use Illuminate\Http\Request;

class DiscordInteractionController extends Controller
{
    private array $commands = [
        'ping' => PingCommand::class,
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

        \Log::debug('Discord headers', [
            'signature' => $request->header('X-Signature-Ed25519'),
            'timestamp' => $request->header('X-Signature-Timestamp'),
        ]);

        return app($handler)($interaction)->toResponse();
    }
}
