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
        $bar = $this->output->createProgressBar();
        $bar->setFormat('%message% [%bar%] %percent%%');
        $bar->setMessage('Downloading ffmpeg...');
        $bar->start();

        try {
            $ffmpeg->install($this->option('force'), $bar);
        } catch (\RuntimeException $e) {
            $bar->finish();
            $this->newLine();
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $bar->finish();
        $this->newLine();
        $this->info('Installed ffmpeg ' . $ffmpeg->version());
        return self::SUCCESS;
    }
}
