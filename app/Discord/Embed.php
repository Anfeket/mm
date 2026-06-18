<?php

namespace App\Discord;

class Embed
{
    private array $data = [];

    public function title(string $title): static
    {
        $this->data['title'] = $title;

        return $this;
    }

    public function url(string $url): static
    {
        $this->data['url'] = $url;

        return $this;
    }

    public function description(string $description): static
    {
        $this->data['description'] = $description;

        return $this;
    }

    public function image(string $url): static
    {
        $this->data['image'] = ['url' => $url];

        return $this;
    }

    public function thumbnail(string $url): static
    {
        $this->data['thumbnail'] = ['url' => $url];

        return $this;
    }

    public function footer(string $text): static
    {
        $this->data['footer'] = ['text' => $text];

        return $this;
    }

    public function timestamp(\DateTimeInterface $dt): static
    {
        $this->data['timestamp'] = $dt->format(\DateTimeInterface::ATOM);

        return $this;
    }

    public function field(string $name, string $value, bool $inline = false): static
    {
        $this->data['fields'][] = compact('name', 'value', 'inline');

        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
