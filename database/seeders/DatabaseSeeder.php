<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Invite;
use App\Models\Tag;
use App\Models\Post;
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
        $user = User::factory()->create([
            'username' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        Invite::create([
            'code' => 'INVITE123',
            'created_by' => $user->id,
        ]);

        $tags = Tag::factory()->count(30)->create([
            'created_by' => $user->id,
        ]);

        $postDir = storage_path('app/uploads/posts');
        $thumbDir = storage_path('app/uploads/posts/thumb');
        if (!is_dir($postDir)) mkdir($postDir, 0775, true);
        if (!is_dir($thumbDir)) mkdir($thumbDir, 0775, true);

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

        foreach ($posts as $post) {
            $post->tags()->attach($tags->random(rand(2, 8))->pluck('id'), ['added_by_user_id' => $user->id]);
        }
    }
}
