<?php

namespace App\Discord;

class Interaction
{
    public function __construct(private readonly array $payload) {}

    public function type(): InteractionType
    {
        return InteractionType::from($this->payload['type']);
    }

    public function commandName(): ?string
    {
        return $this->payload['data']['name'] ?? null;
    }

    // For top-level options: /random, /find id 5
    public function option(string $name): mixed
    {
        $options = $this->payload['data']['options'] ?? [];
        foreach ($options as $opt) {
            if ($opt['name'] === $name) {
                return $opt['value'];
            }
        }

        return null;
    }

    // For subcommands: /find id 5 -> subcommand() = 'id'
    public function subcommand(): ?string
    {
        return $this->payload['data']['options'][0]['name'] ?? null;
    }

    // For subcommand options: /find id 5 -> subOption('id') = 5
    public function subOption(string $name): mixed
    {
        $options = $this->payload['data']['options'][0]['options'] ?? [];
        foreach ($options as $opt) {
            if ($opt['name'] === $name) {
                return $opt['value'];
            }
        }

        return null;
    }

    public function token(): string
    {
        return $this->payload['token'];
    }

    public function raw(): array
    {
        return $this->payload;
    }
}
