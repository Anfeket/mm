<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileStorageService
{
    protected string $disk = 'uploads';

    public function store(UploadedFile $file): array
    {
        $hash = hash_file(config('media.hash'), $file->getRealPath());

        $ext = match ($file->getMimeType()) {
            # Images
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',

            # Videos
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',

            default => throw new \InvalidArgumentException('Unsupported file type: ' . $file->getMimeType()),
        };

        $path = sprintf('%s/%s/%s.%s', substr($hash, 0, 2), substr($hash, 2, 2), $hash, $ext);

        $stream = fopen($file->getRealPath(), 'r');
        Storage::disk($this->disk)->writeStream($path, $stream);
        fclose($stream);

        return [
            'file_path' => $path,
            'file_hash' => $hash,
        ];
    }
}
