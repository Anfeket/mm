<?php

namespace App\Discord\Commands;

use App\Discord\Interaction;
use App\Discord\InteractionResponse;

class PingCommand
{
    public function __invoke(Interaction $interaction): InteractionResponse
    {
        return InteractionResponse::message()->content('Pong!')->ephemeral();
    }
}
