<?php

use App\Services\FfmpegService;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->service = app(FfmpegService::class);
});

test('builds platform specific tar extraction command', function () {
    $archive = 'C:\\temp dir\\ffmpeg.zip';
    $binDir = 'C:\\bin dir';

    $method = new ReflectionMethod(FfmpegService::class, 'buildExtractCommand');
    $method->setAccessible(true);

    $command = $method->invoke($this->service, $archive, $binDir);

    expect($command)->toBeArray()
        ->and($command)->toContain('tar')
        ->and($command)->toContain('-xf')
        ->and($command)->toContain($archive)
        ->and($command)->toContain($binDir)
        ->and(implode(' ', $command))->not->toContain(escapeshellarg($archive))
        ->and(implode(' ', $command))->not->toContain(escapeshellarg($binDir));

    if (PHP_OS_FAMILY === 'Windows') {
        expect($command)->not()->toContain('--wildcards')
            ->and($command)->toContain('*/bin/ffmpeg.exe')
            ->and($command)->toContain('*/bin/ffprobe.exe');

        return;
    }

    expect($command)->toContain('--wildcards')
        ->and($command)->toContain('*/bin/ffmpeg')
        ->and($command)->toContain('*/bin/ffprobe');
});

test('rejects shell-escaped execution arguments', function () {
    $method = new ReflectionMethod(FfmpegService::class, 'assertRawArguments');
    $method->setAccessible(true);

    $method->invoke($this->service, ["'unsafe-input.mp4'"]);
})->throws(InvalidArgumentException::class, 'Pass raw ffmpeg arguments');
