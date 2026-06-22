<?php

namespace App\Discord;

use Illuminate\Http\JsonResponse;

class InteractionResponse
{
    private int $type = 4;

    private array $data = [];

    // type 1 — pong (PING handshake)
    public static function pong(): static
    {
        $response = new static;
        $response->type = 1;

        return $response;
    }

    // type 4 — immediate message response
    public static function message(): static
    {
        return new static;
    }

    // type 5 — acknowledge now, send follow-up later via HTTP
    public function deferred(): static
    {
        $this->type = 5;

        return $this;
    }

    // flag 64 — only visible to the invoking user
    public function ephemeral(): static
    {
        $this->data['flags'] = ($this->data['flags'] ?? 0) | 64;

        return $this;
    }

    public function content(string $content): static
    {
        $this->data['content'] = $content;

        return $this;
    }

    public function embed(Embed $embed): static
    {
        $this->data['embeds'][] = $embed->toArray();

        return $this;
    }

    public function toArray(): array
    {
        $payload = ['type' => $this->type];

        if ($this->data) {
            $payload['data'] = $this->data;
        }

        return $payload;
    }

    public function toResponse(): JsonResponse
    {
        return response()->json($this->toArray());
    }

    public function autocomplete(array $choices): static
    {
        $this->type = 8;
        $this->data['choices'] = $choices;

        return $this;
    }
}
