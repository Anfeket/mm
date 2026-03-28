<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FileStorageService
{
    protected string $disk = 'uploads';

    public function store(UploadedFile $file): array
    {
        $realPath = $file->getRealPath();
        $mimeType = $file->getMimeType();

        $hash = hash_file(config('media.hash'), $realPath);

        $ext = match ($mimeType) {
            // Images
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',

            // Videos
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',

            default => throw new \InvalidArgumentException('Unsupported file type: '.$mimeType),
        };

        $path = sprintf('%s/%s/%s.%s', substr($hash, 0, 2), substr($hash, 2, 2), $hash, $ext);

        $stream = fopen($realPath, 'r');
        Storage::disk($this->disk)->writeStream($path, $stream);
        fclose($stream);

        return [
            'file_path' => $path,
            'file_hash' => $hash,
            'file_size' => $file->getSize(),
            'mime_type' => $mimeType,
            'original_filename' => $file->getClientOriginalName(),
        ];
    }

    public function fileFromUrl(string $url): UploadedFile
    {
        $response = Http::timeout(15)->get($url);
        $response->throw();

        $tmpDir = sys_get_temp_dir();

        $basename = basename(parse_url($url, PHP_URL_PATH)) ?: 'remote_upload';
        $tmpPath = tempnam($tmpDir, 'remote_');

        file_put_contents($tmpPath, $response->body());
        $mimeType = mime_content_type($tmpPath);

        $originalName = $basename;

        $uploadedFile = new UploadedFile(
            $tmpPath,
            $originalName,
            $mimeType,
            null,
            true
        );

        return $uploadedFile;
    }
}
