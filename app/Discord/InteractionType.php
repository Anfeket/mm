<?php

namespace App\Discord;

enum InteractionType: int
{
    case Ping = 1;
    case ApplicationCommand = 2;
    case MessageComponent = 3; // for /search select menus later
    case ApplicationCommandAutocomplete = 4;
    case ModalSubmit = 5;
}
