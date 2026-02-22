<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Helper\ProgressBar;

class FfmpegService
{
    public function __construct()
    {
        $this->default_args = implode(' ', config('media.ffmpeg.default_args', []));
    }

    protected string $default_args;

    public function binaryPath(): string
    {
        $ext = PHP_OS_FAMILY === 'Windows' ? '.exe' : '';
        return storage_path('bin/ffmpeg' . $ext);
    }

    public function probeBinaryPath(): string
    {
        $ext = PHP_OS_FAMILY === 'Windows' ? '.exe' : '';
        return storage_path('bin/ffprobe' . $ext);
    }

    public function isInstalled(): bool
    {
        return file_exists($this->binaryPath());
    }

    public function version(): ?string
    {
        if (!$this->isInstalled()) {
            return null;
        }

        $cmd = escapeshellarg($this->binaryPath()) . ' -version 2>&1';
        exec($cmd, $output, $returnCode);

        return $output[0] ?? null;
    }

    public function install(bool $force = false, ?ProgressBar $progress = null): void
    {
        $binDir = storage_path('bin');
        $binPath = $this->binaryPath();

        if (file_exists($binPath) && !$force) {
            throw new \RuntimeException('ffmpeg already installed');
        }

        if (!is_dir($binDir)) {
            mkdir($binDir, 0755, true);
        }

        $progress?->setMessage('Downloading ffmpeg...');

        $archive = $binDir . '/ffmpeg.tar.xz';
        Http::withOptions([
            'progress' => function ($total, $downloaded) use ($progress) {
                if ($total > 0 && $progress) {
                    $progress->setMaxSteps($total);
                    $progress->setProgress($downloaded);
                }
            },
            'sink' => $archive,
        ])->get(config('media.ffmpeg.url'));

        $progress->setMessage('Extracting ffmpeg...');
        exec("tar -xf {$archive} -C {$binDir} --strip-components=2 --wildcards '*/bin/ffmpeg' '*/bin/ffprobe'", $output, $returnCode);

        $progress->setMessage('Cleaning up...');
        unlink($archive);

        if ($returnCode !== 0 || !file_exists($binPath)) {
            throw new \RuntimeException('Failed to install ffmpeg');
        }

        chmod($binPath, 0755);
        $progress->setMessage('ffmpeg installed successfully');
    }

    public function exec(string $args): array
    {
        if (!$this->isInstalled()) {
            throw new \RuntimeException('ffmpeg not installed');
        }

        $cmd = escapeshellarg($this->binaryPath()) . ' ' . $this->default_args . ' ' . $args . ' 2>&1';
        exec($cmd, $output, $returnCode);

        return [
            'output' => $output,
            'returnCode' => $returnCode,
        ];
    }

    public function probe(string $args): array
    {
        if (!$this->isInstalled()) {
            throw new \RuntimeException('ffmpeg not installed');
        }

        $cmd = escapeshellarg($this->probeBinaryPath()) . ' ' . $this->default_args . ' ' . $args . ' 2>&1';
        exec($cmd, $output, $returnCode);

        return [
            'output' => $output,
            'returnCode' => $returnCode,
        ];
    }
}
