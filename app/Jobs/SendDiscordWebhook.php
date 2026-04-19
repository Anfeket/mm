<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\DiscordService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendDiscordWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly Post $post
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DiscordService $discord): void
    {
        $discord->sendNewPost($this->post);
    }
}
