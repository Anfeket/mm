<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\AvatarProcessed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ProcessAvatar implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly User $user,
        public readonly string $avatarPath,
        public readonly ?array $crop = null,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $realPath = Storage::path($this->avatarPath);

        try {
            $dir = storage_path('app/uploads/avatars/');
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $path = "avatars/{$this->user->id}.webp";
            $dest = storage_path('app/uploads/'.$path);

            if ($this->isAnimated($realPath)) {
                $this->processAvatarAnimated($realPath, $dest, $this->crop);
            } else {
                $this->processAvatarStatic($realPath, $dest, $this->crop);
            }

            $this->user->avatar_path = $path;
            $this->user->save();

            $this->user->notify(new AvatarProcessed(success: true));
        } catch (\Throwable $e) {
            $this->user->notify(new AvatarProcessed(success: false));
            throw $e;
        } finally {
            Storage::delete($this->avatarPath);
        }
    }

    private function processAvatarStatic(string $realPath, string $dest, ?array $crop = null): void
    {
        $source = imagecreatefromstring(file_get_contents($realPath));

        if (! $source) {
            throw new \RuntimeException('Could not read avatar image');
        }

        $origW = imagesx($source);
        $origH = imagesy($source);
        $thumbSize = config('media.avatar.size', 128);

        $thumb = imagecreatetruecolor($thumbSize, $thumbSize);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);

        if ($crop) {
            $cropX = max(0, min($crop['x'], $origW - 1));
            $cropY = max(0, min($crop['y'], $origH - 1));
            $cropSize = max(1, min($crop['size'], $origW - $cropX, $origH - $cropY));

            imagecopyresampled(
                $thumb,
                $source,
                0,
                0,
                $cropX,
                $cropY,
                $thumbSize,
                $thumbSize,
                $cropSize,
                $cropSize
            );
        } else {
            $scale = max($thumbSize / $origW, $thumbSize / $origH);
            $thumbW = (int) ($origW * $scale);
            $thumbH = (int) ($origH * $scale);
            $offsetX = (int) (($thumbW - $thumbSize) / 2);
            $offsetY = (int) (($thumbH - $thumbSize) / 2);

            $scaled = imagecreatetruecolor($thumbW, $thumbH);
            imagealphablending($scaled, false);
            imagesavealpha($scaled, true);
            imagecopyresampled($scaled, $source, 0, 0, 0, 0, $thumbW, $thumbH, $origW, $origH);
            imagecopy($thumb, $scaled, 0, 0, $offsetX, $offsetY, $thumbW, $thumbH);
        }

        imagewebp($thumb, $dest, config('media.avatar.quality', 80));
    }

    private function processAvatarAnimated(string $realPath, string $dest, ?array $crop = null): void
    {
        $thumbSize = config('media.avatar.size', 128);
        $quality = config('media.avatar.quality', 80);

        $imagick = new \Imagick;
        $imagick->readImage($realPath);
        $imagick = $imagick->coalesceImages();

        $origW = $imagick->current()->getImageWidth();
        $origH = $imagick->current()->getImageHeight();

        // Calculate crop/scale params once, same for all frames
        if ($crop) {
            $cropX = max(0, min($crop['x'], $origW - 1));
            $cropY = max(0, min($crop['y'], $origH - 1));
            $cropSize = max(1, min($crop['size'], $origW - $cropX, $origH - $cropY));
        } else {
            $cropSize = (int) (min($origW, $origH));
            $cropX = (int) (($origW - $cropSize) / 2);
            $cropY = (int) (($origH - $cropSize) / 2);
        }

        foreach ($imagick as $frame) {
            $frame->cropImage($cropSize, $cropSize, $cropX, $cropY);
            $frame->thumbnailImage($thumbSize, $thumbSize);
            $frame->setImagePage($thumbSize, $thumbSize, 0, 0);
            $frame->setImageCompressionQuality($quality);
        }

        $imagick = $imagick->deconstructImages();
        $imagick->setFormat('webp');
        $imagick->setOption('webp:loop', '0');

        file_put_contents($dest, $imagick->getImagesBlob());
        $imagick->clear();
    }

    private function isAnimated(string $realPath): bool
    {
        $mime = mime_content_type($realPath);

        if (! in_array($mime, ['image/gif', 'image/webp'])) {
            return false;
        }

        $imagick = new \Imagick;
        $imagick->pingImage($realPath);
        $frames = $imagick->getNumberImages();
        $imagick->clear();

        return $frames > 1;
    }
}
