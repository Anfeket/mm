<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{

    protected $model = Post::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        if (app()->environment('testing')) {
            return $this->fakeDefinition();
        }

        $filename = $this->faker->lexify('sample_????');

        $width = $this->faker->numberBetween(400, 1200);
        $height = $this->faker->numberBetween(400, 1200);
        $imagedata = file_get_contents("https://picsum.photos/{$width}/{$height}.jpg");

        $postDir = storage_path('app/uploads/posts');
        $thumbDir = storage_path('app/uploads/posts/thumb');

        file_put_contents("{$postDir}/{$filename}.jpg", $imagedata);
        $source = imagecreatefromstring($imagedata);
        $origW = imagesx($source);
        $origH = imagesy($source);
        $scale = min(200 / $origW, 200 / $origH);
        $thumbW = (int)($origW * $scale);
        $thumbH = (int)($origH * $scale);
        $thumb = imagecreatetruecolor($thumbW, $thumbH);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbW, $thumbH, $origW, $origH);
        imagewebp($thumb, "{$thumbDir}/{$filename}.webp", 80);

        return [
            'author_id' => User::factory(),
            'description' => $this->faker->sentence(),
            'file_path' => "posts/{$filename}" . ".jpg",
            'thumb_path' => "posts/thumb/{$filename}" . ".webp",
            'source_url' => $this->faker->url(),
            'original_filename' => $filename,
            'mime_type' => 'image/jpeg',
            'width' => $width,
            'height' => $height,
            'file_size' => strlen($imagedata),
            'file_hash' => hash(config('media.hash'), $imagedata),
            'is_listed' => true,
        ];
    }

    public function unlisted(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_listed' => false,
        ]);
    }

    public function nsfw(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_nsfw' => true,
        ]);
    }

    private function fakeDefinition(): array
    {
        return [
            'author_id'         => User::factory(),
            'file_path'         => 'posts/fake.jpg',
            'thumb_path'        => 'posts/thumb/fake.webp',
            'original_filename' => 'fake.jpg',
            'mime_type'         => 'image/jpeg',
            'width'             => 800,
            'height'            => 600,
            'file_size'         => 1024,
            'file_hash'         => hash(config('media.hash'), uniqid()),
            'is_listed'         => true,
            'processing_status' => \App\PostProcessingStatus::Completed,
        ];
    }
}
