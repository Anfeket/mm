<?php

use App\Jobs\ProcessPostMedia;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function tinyPngBinary(): string
{
    return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7+XxQAAAAASUVORK5CYII=');
}

beforeEach(function () {
    Storage::fake('uploads');
    Queue::fake();
    $this->user = User::factory()->create();
});

describe('Post Upload', function () {
    it('allows uploading via URL only (valid image)', function () {
        Http::fake([
            'example.com/*' => Http::response(
                tinyPngBinary(),
                200,
                ['Content-Type' => 'image/png']
            ),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('posts.store'), [
                'url' => 'https://example.com/meme.png',
                'description' => 'URL meme',
            ]);

        $response->assertRedirect();
        expect(Post::count())->toBe(1);

        $post = Post::first();
        expect($post->description)->toBe('URL meme')
            ->and($post->mime_type)->toBe('image/png')
            ->and($post->file_size)->toBeGreaterThan(0);

        Queue::assertPushed(ProcessPostMedia::class);
    });

    it('allows uploading via file only (valid image)', function () {
        $file = UploadedFile::fake()->image('meme.jpg', 600, 400);
        $response = $this->actingAs($this->user)
            ->post(route('posts.store'), [
                'file' => $file,
                'description' => 'Test meme',
            ]);
        $response->assertRedirect();
        expect(Post::count())->toBe(1);
        $post = Post::first();
        expect($post->description)->toBe('Test meme');

        Queue::assertPushed(ProcessPostMedia::class);
    });

    it('returns validation error if both file and url are filled', function () {
        $file = UploadedFile::fake()->image('meme.jpg');
        $response = $this->actingAs($this->user)
            ->post(route('posts.store'), [
                'file' => $file,
                'url' => 'https://example.com/meme.jpg',
            ]);
        $response->assertSessionHasErrors(['file', 'url']);
    });

    it('returns validation error if neither file nor url is filled', function () {
        $response = $this->actingAs($this->user)
            ->post(route('posts.store'), []);
        $response->assertSessionHasErrors(['file', 'url']);
    });

    it('rejects upload if url is not an image/video or has wrong mimetype', function () {
        Http::fake([
            'example.com/*' => Http::response(
                '<html>not media</html>',
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('posts.store'), [
                'url' => 'https://example.com/not-media',
            ]);

        $response->assertSessionHasErrors(['file']);
        expect(Post::count())->toBe(0);
    });

    it('returns error if url is unreachable', function () {
        $response = $this->actingAs($this->user)
            ->post(route('posts.store'), [
                'url' => 'http://localhost:9999/nonexistent.jpg',
            ]);
        $response->assertSessionHasErrors(['file']);
    });

    it('handles url redirects gracefully', function () {
        Http::fake(function (Request $request) {
            if ($request->url() === 'https://example.com/redirect-image') {
                return Http::response('', 302, ['Location' => 'https://cdn.example.com/final.png']);
            }

            if ($request->url() === 'https://cdn.example.com/final.png') {
                return Http::response(
                    tinyPngBinary(),
                    200,
                    ['Content-Type' => 'image/png']
                );
            }

            return Http::response('', 404);
        });

        $response = $this->actingAs($this->user)
            ->post(route('posts.store'), [
                'url' => 'https://example.com/redirect-image',
                'description' => 'Redirect meme',
            ]);

        $response->assertRedirect();
        expect(Post::count())->toBe(1);

        $post = Post::first();
        expect($post->description)->toBe('Redirect meme')
            ->and($post->mime_type)->toBe('image/png')
            ->and($post->file_size)->toBeGreaterThan(0);

        Queue::assertPushed(ProcessPostMedia::class);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://example.com/redirect-image');
    });
});
