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

        $posts = Post::factory()->count(20)->create([
            'author_id' => $user->id,
        ]);

        foreach ($posts as $post) {
            $post->tags()->attach($tags->random(rand(2, 8))->pluck('id'), ['added_by_user_id' => $user->id]);
        }
    }
}
