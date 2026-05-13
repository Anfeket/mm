<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        return storage_path('bin/ffmpeg'.$ext);
    }

    public function probeBinaryPath(): string
    {
        $ext = PHP_OS_FAMILY === 'Windows' ? '.exe' : '';

        return storage_path('bin/ffprobe'.$ext);
    }

    public function isInstalled(): bool
    {
        return file_exists($this->binaryPath());
    }

    public function version(): ?string
    {
        if (! $this->isInstalled()) {
            return null;
        }

        $cmd = escapeshellarg($this->binaryPath()).' -version 2>&1';
        exec($cmd, $output, $returnCode);

        return $output[0] ?? null;
    }

    /**
     * Get the download URL for ffmpeg, using config if set, otherwise auto-detect based on OS/arch.
     */
    public function getFfmpegUrl(): string
    {
        $customUrl = config('media.ffmpeg.url');
        if ($customUrl) {
            return $customUrl;
        }

        $os = PHP_OS_FAMILY;
        $arch = strtolower(php_uname('m'));

        // Normalize architecture (case-insensitive)
        if (in_array($arch, ['x86_64', 'amd64'])) {
            $arch = '64';
        } elseif (in_array($arch, ['aarch64', 'arm64'])) {
            $arch = 'arm64';
        } else {
            throw new \RuntimeException("Unsupported architecture: $arch");
        }

        // Map OS to BtbN naming
        if ($os === 'Windows') {
            $osPart = 'win'.$arch;
            $ext = 'zip';
        } elseif ($os === 'Linux') {
            $osPart = 'linux'.$arch;
            $ext = 'tar.xz';
        } elseif ($os === 'Darwin') {
            $osPart = 'macos'.$arch;
            $ext = 'zip';
        } else {
            throw new \RuntimeException("Unsupported OS: $os");
        }

        return "https://github.com/BtbN/FFmpeg-Builds/releases/download/latest/ffmpeg-master-latest-{$osPart}-gpl.{$ext}";
    }

    protected function buildExtractCommand(string $archive, string $binDir): string
    {
        $archiveArg = escapeshellarg($archive);
        $binDirArg = escapeshellarg($binDir);

        if (PHP_OS_FAMILY === 'Windows') {
            return "tar -xf {$archiveArg} -C {$binDirArg} --strip-components=2 \"*/bin/ffmpeg.exe\" \"*/bin/ffprobe.exe\"";
        }

        return "tar -xf {$archiveArg} -C {$binDirArg} --strip-components=2 --wildcards '*/bin/ffmpeg' '*/bin/ffprobe'";
    }

    public function install(bool $force = false, ?ProgressBar $progress = null): void
    {
        $binDir = storage_path('bin');
        $binPath = $this->binaryPath();

        if (file_exists($binPath) && ! $force) {
            throw new \RuntimeException('ffmpeg already installed');
        }

        if (! is_dir($binDir)) {
            mkdir($binDir, 0755, true);
        }

        $progress?->setMessage('Downloading ffmpeg...');

        $archiveExt = PHP_OS_FAMILY === 'Linux' ? 'tar.xz' : 'zip';
        $archive = $binDir.'/ffmpeg.'.$archiveExt;
        $url = $this->getFfmpegUrl();
        Http::withOptions([
            'progress' => function ($total, $downloaded) use ($progress) {
                if ($total > 0 && $progress) {
                    $progress->setMaxSteps($total);
                    $progress->setProgress($downloaded);
                }
            },
            'sink' => $archive,
        ])->get($url);

        $progress->setMessage('Extracting ffmpeg...');
        $tarCmd = $this->buildExtractCommand($archive, $binDir);

        exec($tarCmd, $output, $returnCode);

        $progress->setMessage('Cleaning up...');
        unlink($archive);

        if ($returnCode !== 0 || ! file_exists($binPath)) {
            throw new \RuntimeException('Failed to install ffmpeg');
        }

        chmod($binPath, 0755);
        $progress->setMessage('ffmpeg installed successfully');

        // Log the installed version and URL used
        Log::info('FFmpeg installed', [
            'version' => $this->version(),
            'url' => $url,
        ]);
    }

    public function exec(string $args): array
    {
        if (! $this->isInstalled()) {
            throw new \RuntimeException('ffmpeg not installed');
        }

        $cmd = escapeshellarg($this->binaryPath()).' '.$this->default_args.' '.$args.' 2>&1';
        exec($cmd, $output, $returnCode);

        return [
            'output' => $output,
            'returnCode' => $returnCode,
        ];
    }

    public function probe(string $args): array
    {
        if (! $this->isInstalled()) {
            throw new \RuntimeException('ffmpeg not installed');
        }

        $cmd = escapeshellarg($this->probeBinaryPath()).' '.$this->default_args.' '.$args.' 2>&1';
        exec($cmd, $output, $returnCode);

        return [
            'output' => $output,
            'returnCode' => $returnCode,
        ];
    }
}
