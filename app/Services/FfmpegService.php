<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Process\Process;

class FfmpegService
{
    public function __construct()
    {
        $this->defaultArgs = $this->normalizeDefaultArgs(config('media.ffmpeg.default_args', []));
    }

    protected array $defaultArgs;

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

        $result = $this->runProcess($this->binaryPath(), ['-version']);

        return $result['output'][0] ?? null;
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

    protected function buildExtractCommand(string $archive, string $binDir): array
    {
        $command = ['tar', '-xf', $archive, '-C', $binDir, '--strip-components=2'];

        if (PHP_OS_FAMILY === 'Windows') {
            return [...$command, '*/bin/ffmpeg.exe', '*/bin/ffprobe.exe'];
        }

        return [...$command, '--wildcards', '*/bin/ffmpeg', '*/bin/ffprobe'];
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
        $extract = new Process($this->buildExtractCommand($archive, $binDir));
        $extract->run();

        $progress->setMessage('Cleaning up...');
        unlink($archive);

        if (! $extract->isSuccessful() || ! file_exists($binPath)) {
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

    /**
     * Execute ffmpeg with raw argument tokens.
     * Do not pass shell-escaped values (e.g. escapeshellarg output).
     */
    public function exec(array $args): array
    {
        if (! $this->isInstalled()) {
            throw new \RuntimeException('ffmpeg not installed');
        }

        return $this->runProcess($this->binaryPath(), $args);
    }

    /**
     * Execute ffprobe with raw argument tokens.
     * Do not pass shell-escaped values (e.g. escapeshellarg output).
     */
    public function probe(array $args): array
    {
        if (! $this->isInstalled()) {
            throw new \RuntimeException('ffmpeg not installed');
        }

        return $this->runProcess($this->probeBinaryPath(), $args);
    }

    protected function runProcess(string $binaryPath, array $args): array
    {
        $this->assertRawArguments($args);

        $process = new Process([$binaryPath, ...$this->defaultArgs, ...$args]);
        $process->run();

        $combinedOutput = trim($process->getOutput()."\n".$process->getErrorOutput());
        $output = $combinedOutput === '' ? [] : preg_split("/\r\n|\r|\n/", $combinedOutput);

        return [
            'output' => $output ?: [],
            'returnCode' => $process->getExitCode() ?? 1,
        ];
    }

    protected function assertRawArguments(array $args): void
    {
        foreach ($args as $arg) {
            if (! is_string($arg)) {
                throw new \InvalidArgumentException('All ffmpeg arguments must be strings.');
            }

            if ((str_starts_with($arg, "'") && str_ends_with($arg, "'")) || (str_starts_with($arg, '"') && str_ends_with($arg, '"'))) {
                throw new \InvalidArgumentException('Pass raw ffmpeg arguments, not shell-escaped values.');
            }
        }
    }

    protected function normalizeDefaultArgs(array $args): array
    {
        $normalized = [];

        foreach ($args as $arg) {
            if (! is_string($arg)) {
                continue;
            }

            $parts = preg_split('/\s+/', trim($arg));
            if (! is_array($parts)) {
                continue;
            }

            foreach ($parts as $part) {
                if ($part !== '') {
                    $normalized[] = $part;
                }
            }
        }

        return $normalized;
    }
}
