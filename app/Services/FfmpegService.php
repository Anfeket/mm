<?php

namespace App\Services;

class FfmpegService
{
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

    public function install(bool $force = false): void
    {
        $binDir = storage_path('bin');
        $binPath = $this->binaryPath();

        if (file_exists($binPath) && !$force) {
            throw new \RuntimeException('ffmpeg already installed');
        }

        if (!is_dir($binDir)) {
            mkdir($binDir, 0755, true);
        }

        $archive = $binDir . '/ffmpeg.tar.xz';
        file_put_contents($archive, fopen(config('media.ffmpeg_url'), 'r'));

        exec("tar -xf {$archive} -C {$binDir} --strip-components=2 --wildcards '*/bin/ffmpeg' '*/bin/ffprobe'", $output, $returnCode);

        unlink($archive);

        if ($returnCode !== 0 || !file_exists($binPath)) {
            throw new \RuntimeException('Failed to install ffmpeg');
        }

        chmod($binPath, 0755);
    }

    public function exec(string $args): array
    {
        if (!$this->isInstalled()) {
            throw new \RuntimeException('ffmpeg not installed');
        }

        $cmd = escapeshellarg($this->binaryPath()) . ' ' . $args . ' 2>&1';
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

        $cmd = escapeshellarg($this->probeBinaryPath()) . ' ' . $args . ' 2>&1';
        exec($cmd, $output, $returnCode);

        return [
            'output' => $output,
            'returnCode' => $returnCode,
        ];
    }
}
