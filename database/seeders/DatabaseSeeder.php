<?php

namespace Database\Seeders;

use App\Models\Invite;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create an admin user
        $user = User::factory()->create([
            'username' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->role = 'admin';
        $user->save();

        // Create an invite code for registration
        Invite::create([
            'code' => 'INVITE123',
            'created_by' => $user->id,
        ]);

        // Create 30 tags
        $tags = Tag::factory()->count(30)->create([
            'created_by' => $user->id,
        ]);

        // Ensure upload directories exist
        $postDir = storage_path('app/uploads/posts');
        $thumbDir = storage_path('app/uploads/posts/thumb');
        if (! is_dir($postDir)) {
            mkdir($postDir, 0775, true);
        }
        if (! is_dir($thumbDir)) {
            mkdir($thumbDir, 0775, true);
        }

        // Create 20 posts with a progress bar
        $bar = $this->command?->getOutput()->createProgressBar(20);
        $bar?->setFormat('%message% [%bar%] %current%/%max%');
        $bar?->setMessage('Creating posts...');
        $bar?->start();

        $posts = collect(range(1, 20))->map(function () use ($user, $bar) {
            $post = Post::factory()->createOne(['author_id' => $user->id]);
            $bar?->advance();

            return $post;
        });

        $bar?->finish();
        $this->command?->getOutput()->newLine();

        // Attach 2-8 random tags to each post
        foreach ($posts as $post) {
            $post->tags()->attach($tags->random(rand(2, 8))->pluck('id'), ['added_by_user_id' => $user->id]);
        }

        // Add random votes to posts: create 10 users, each post gets 0-10 random votes from these users with random +1/-1 values
        $voters = User::factory()->count(10)->create();
        foreach ($posts as $post) {
            $votersForPost = $voters->random(rand(0, 10));
            foreach ($votersForPost as $voter) {
                $voter->votes()->create([
                    'post_id' => $post->id,
                    'value' => rand(0, 1) ? 1 : -1,
                ]);
            }
        }

        // Recalculate post_count for all tags after seeding
        Tag::query()->each(function (Tag $tag) {
            $tag->post_count = $tag->posts()->count();
            $tag->save();
        });
    }
}
