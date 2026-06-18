<?php

namespace App\Http\Controllers;

use App\Discord\Commands\PingCommand;
use Illuminate\Http\Request;
use App\Discord\Interaction;
use App\Discord\InteractionResponse;
use App\Discord\InteractionType;

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

        if (!$handler) {
            return InteractionResponse::message()
                ->content("Unknown command: $name")
                ->ephemeral()
                ->toResponse();
        }

        return app($handler)($interaction)->toResponse();
    }
}
