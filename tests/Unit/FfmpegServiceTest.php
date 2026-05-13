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

    $archiveArg = escapeshellarg($archive);
    $binDirArg = escapeshellarg($binDir);

    $method = new ReflectionMethod(FfmpegService::class, 'buildExtractCommand');
    $method->setAccessible(true);

    $command = $method->invoke($this->service, $archive, $binDir);

    expect($command)->toContain($archiveArg)
        ->and($command)->toContain($binDirArg);

    if (PHP_OS_FAMILY === 'Windows') {
        expect($command)->not()->toContain('--wildcards')
            ->and($command)->toContain('"*/bin/ffmpeg.exe"')
            ->and($command)->toContain('"*/bin/ffprobe.exe"');

        return;
    }

    expect($command)->toContain('--wildcards')
        ->and($command)->toContain('*/bin/ffmpeg')
        ->and($command)->toContain('*/bin/ffprobe');
});
