<?php

namespace App\Console\Commands;

use App\Services\FfmpegService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ffmpeg:version')]
#[Description('Show installed ffmpeg version')]
class FFmpegVersion extends Command
{
    public function handle(FfmpegService $ffmpeg): int
    {
        $version = $ffmpeg->version();
        if ($version) {
            $this->info('Installed ffmpeg version: ' . $version);
            return self::SUCCESS;
        } else {
            $this->error('ffmpeg is not installed');
            return self::FAILURE;
        }
    }
}
