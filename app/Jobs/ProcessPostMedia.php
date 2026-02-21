<?php

namespace App\Jobs;

use App\Services\FfmpegService;
use App\Models\Post;
use App\PostProcessingStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPostMedia implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Post $post)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(FfmpegService $ffmpeg): void
    {
        try {
            $fullPath   = storage_path('app/uploads/' . $this->post->file_path);
            $relThumbPath = $this->thumbPath();
            $absThumbPath  = storage_path('app/uploads/' . $this->thumbPath());

            if (!is_dir(dirname($absThumbPath))) {
                mkdir(dirname($absThumbPath), 0755, true);
            }

            if ($this->post->isImage()) {
                $this->generateImageThumb($fullPath, $absThumbPath);
                [$width, $height] = getimagesize($fullPath);
                $this->post->width  = $width;
                $this->post->height = $height;
            } elseif ($this->post->isVideo()) {
                $this->generateVideoThumb($fullPath, $absThumbPath, $ffmpeg);
                [$width, $height] = $this->getVideoDimensions($fullPath, $ffmpeg);
                $duration = $this->getVideoDuration($fullPath, $ffmpeg);
                $this->post->width          = $width;
                $this->post->height         = $height;
                $this->post->duration_ms    = $duration;
            }

            $this->post->thumb_path         = $relThumbPath;
            $this->post->processing_status  = PostProcessingStatus::Completed;
            $this->post->is_listed          = true;
            $this->post->save();
        } catch (\Throwable $e) {
            $this->post->processing_status = PostProcessingStatus::Failed;
            $this->post->processing_error  = $e->getMessage();
            $this->post->save();

            throw $e;
        }
    }

    private function thumbPath(): string
    {
        $hash = $this->post->file_hash;
        return sprintf('thumb/%s/%s/%s.webp', substr($hash, 0, 2), substr($hash, 2, 2), $hash);
    }

    private function generateImageThumb(string $src, string $dest): void
    {
        $data   = file_get_contents($src);
        $source = imagecreatefromstring($data);

        if (!$source) {
            throw new \RuntimeException("Could not read image {$src}");
        }

        $width  = imagesx($source);
        $height = imagesy($source);
        [$thumbWidth, $thumbHeight] = [config('media.thumb.width'), config('media.thumb.height')];
        $scale = min($thumbWidth / $width, $thumbHeight / $height);
        $fitWidth = (int)($width * $scale);
        $fitHeight = (int)($height * $scale);

        $thumb = imagecreatetruecolor($fitWidth, $fitHeight);

        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);

        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $fitWidth, $fitHeight, $width, $height);
        imagewebp($thumb, $dest, config('media.thumb.quality'));
    }

    private function generateVideoThumb(string $src, string $dest, FfmpegService $ffmpeg): void
    {
        $result = $ffmpeg->exec(sprintf(
            '-ss %d -i %s -frames:v 1 -vf "scale=%d:%d:force_original_aspect_ratio=decrease" %s',
            escapeshellarg($src),
            config('media.thumb.video_frame_time'),
            config('media.thumb.width'),
            config('media.thumb.height'),
            escapeshellarg($dest)
        ));

        if ($result['returnCode'] !== 0) {
            throw new \RuntimeException('ffmpeg failed to generate video thumbnail: ' . implode("\n", $result['output']));
        }
    }

    private function getVideoDimensions(string $src, FfmpegService $ffmpeg): array
    {
        $result = $ffmpeg->probe(sprintf(
            '-v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0 %s',
            escapeshellarg($src)
        ));

        if ($result['returnCode'] !== 0 || empty($result['output'])) {
            throw new \RuntimeException('ffprobe failed to read dimensoins: ' . implode("\n", $result['output']));
        }

        [$width, $height] = explode(',', $result['output'][0]);
        return [(int)$width, (int)$height];
    }

    private function getVideoDuration(string $src, FfmpegService $ffmpeg): int
    {
        $result = $ffmpeg->probe(sprintf(
            '-v error -show_entries format=duration -of csv=p=0 %s',
            escapeshellarg($src)
        ));

        if ($result['returnCode'] !== 0 || empty($result['output'])) {
            throw new \RuntimeException('ffprobe failed to read duration: ' . implode("\n", $result['output']));
        }

        return (int)($result['output'][0] * 1000); // Convert to milliseconds
    }
}
