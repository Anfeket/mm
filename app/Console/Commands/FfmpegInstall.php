<?php

namespace App\Console\Commands;

use App\Services\FfmpegService;
use Illuminate\Console\Command;

class FfmpegInstall extends Command
{
    protected $signature    = 'ffmpeg:install {--force}';
    protected $description  = 'Install latest static ffmpeg binary. Use --force to reinstall or update.';

    public function handle(FfmpegService $ffmpeg): int
    {
        try {
            $ffmpeg->install($this->option('force'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Installed ffmpeg ' . $ffmpeg->version());
        return self::SUCCESS;
    }
}
