<?php

use App\Jobs\ProcessPostMedia;
use App\Models\Post;
use App\Models\User;
use App\PostProcessingStatus;
use App\Services\FfmpegService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

function createPendingImagePost(string $filePath, string $fileHash, string $mimeType = 'image/webp'): Post
{
    return Post::create([
        'author_id' => User::factory()->create()->id,
        'mime_type' => $mimeType,
        'file_hash' => $fileHash,
        'file_path' => $filePath,
        'file_size' => filesize(storage_path('app/uploads/'.$filePath)),
        'original_filename' => basename($filePath),
        'is_listed' => false,
        'processing_status' => PostProcessingStatus::Processing,
    ]);
}

function ensureUploadDirectory(string $hash): string
{
    $relativeDir = sprintf('%s/%s', substr($hash, 0, 2), substr($hash, 2, 2));
    $absoluteDir = storage_path('app/uploads/'.$relativeDir);

    if (! is_dir($absoluteDir)) {
        mkdir($absoluteDir, 0755, true);
    }

    return $relativeDir;
}

it('generates static webp thumbnails via gd', function () {
    $hash = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    $relativeDir = ensureUploadDirectory($hash);
    $relativePath = $relativeDir.'/'.$hash.'.webp';
    $absolutePath = storage_path('app/uploads/'.$relativePath);

    $image = imagecreatetruecolor(8, 6);
    imagewebp($image, $absolutePath, 80);
    imagedestroy($image);

    $post = createPendingImagePost($relativePath, $hash);

    $ffmpeg = Mockery::mock(FfmpegService::class);
    $ffmpeg->shouldNotReceive('exec');
    $ffmpeg->shouldNotReceive('probe');

    (new ProcessPostMedia($post))->handle($ffmpeg);

    $post->refresh();

    expect($post->processing_status)->toBe(PostProcessingStatus::Completed)
        ->and($post->is_listed)->toBe(1)
        ->and($post->width)->toBe(8)
        ->and($post->height)->toBe(6)
        ->and($post->thumb_path)->toBe(sprintf('thumb/%s/%s/%s.webp', substr($hash, 0, 2), substr($hash, 2, 2), $hash));

    expect(file_exists(storage_path('app/uploads/'.$post->thumb_path)))->toBeTrue();
});

it('detects animated webp and routes thumbnail generation through ffmpeg', function () {
    $hash = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';
    $relativeDir = ensureUploadDirectory($hash);
    $relativePath = $relativeDir.'/'.$hash.'.webp';
    $absolutePath = storage_path('app/uploads/'.$relativePath);

    $header = 'RIFF'.pack('V', 30).'WEBP'.'VP8X'.pack('V', 10).chr(0b00000010);
    file_put_contents($absolutePath, $header.str_repeat("\0", 64));

    $post = createPendingImagePost($relativePath, $hash);

    $ffmpeg = Mockery::mock(FfmpegService::class);
    $ffmpeg->shouldReceive('exec')
        ->once()
        ->andReturn([
            'output' => [],
            'returnCode' => 0,
        ]);

    $ffmpeg->shouldReceive('probe')
        ->once()
        ->andReturn([
            'output' => ['640,480'],
            'returnCode' => 0,
        ]);

    (new ProcessPostMedia($post))->handle($ffmpeg);

    $post->refresh();

    expect($post->processing_status)->toBe(PostProcessingStatus::Completed)
        ->and($post->is_listed)->toBe(1)
        ->and($post->width)->toBe(640)
        ->and($post->height)->toBe(480)
        ->and($post->duration_ms)->toBeNull();
});

it('does not route non-webp images through ffmpeg', function () {
    $hash = 'cccccccccccccccccccccccccccccccc';
    $relativeDir = ensureUploadDirectory($hash);
    $relativePath = $relativeDir.'/'.$hash.'.jpg';
    $absolutePath = storage_path('app/uploads/'.$relativePath);

    $image = imagecreatetruecolor(11, 7);
    imagejpeg($image, $absolutePath, 90);
    imagedestroy($image);

    $post = createPendingImagePost($relativePath, $hash, 'image/jpeg');

    $ffmpeg = Mockery::mock(FfmpegService::class);
    $ffmpeg->shouldNotReceive('exec');
    $ffmpeg->shouldNotReceive('probe');

    (new ProcessPostMedia($post))->handle($ffmpeg);

    $post->refresh();

    expect($post->processing_status)->toBe(PostProcessingStatus::Completed)
        ->and($post->is_listed)->toBe(1)
        ->and($post->width)->toBe(11)
        ->and($post->height)->toBe(7)
        ->and($post->duration_ms)->toBeNull();
});
