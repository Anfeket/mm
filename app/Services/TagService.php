<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\Post;
use App\TagCategory;
use Illuminate\Support\Facades\Auth;

class TagService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function normalizeTagName(string $name): string
    {
        $name = mb_strtolower($name); // Convert to lowercase
        $name = preg_replace('/\s+/', '_', $name); // Replace spaces with underscores
        $name = preg_replace('/[^a-z0-9_():-]/', '', $name); // Remove special characters except _, :, () and -
        $name = preg_replace('/_+/', '_', $name); // Replace multiple underscores with a single one
        $name = trim($name, '_'); // Remove leading and trailing underscores
        return $name;
    }

    public function parseInput(string $input, TagCategory $defaultCategory = TagCategory::General): array
    {
        $tags = [];

        foreach (preg_split('/\s+/', trim($input)) as $token) {
            if (empty($token)) {
                continue;
            }

            if (preg_match('/^([a-z]+):(.+)$/i', $token, $matches)) {
                $category = TagCategory::fromPrefix(strtolower($matches[1]));

                if ($category !== null) {
                    $name = $this->normalizeTagName($matches[2]);
                } else {
                    $category = $defaultCategory;
                    $name = $this->normalizeTagName($token);
                }
            } else {
                $category = $defaultCategory;
                $name = $this->normalizeTagName($token);
            }

            if (!empty($name)) {
                $tags[] = ['name' => $name, 'category' => $category];
            }
        }

        return $tags;
    }

    public function parseSingleTag(string $token, TagCategory $defaultCategory = TagCategory::General): ?array
    {
        $input = trim($token);
        if (empty($input)) {
            return null;
        }

        if (preg_match('/^([a-z]+):(.+)$/i', $token, $matches)) {
            $category = TagCategory::fromPrefix(strtolower($matches[1]));

            if ($category !== null) {
                $name = $this->normalizeTagName($matches[2]);
            } else {
                $category = $defaultCategory;
                $name = $this->normalizeTagName($token);
            }
        } else {
            $category = $defaultCategory;
            $name = $this->normalizeTagName($token);
        }

        if (empty($name)) {
            return null;
        }

        return ['name' => $name, 'category' => $category];
    }

    public function resolveAlias(Tag $tag): Tag
    {
        $visited = [];
        while ($tag->alias_tag_id !== null) {
            if (in_array($tag->id, $visited)) {
                throw new \RuntimeException(
                    "Circular alias detected for tag #{$tag->id} ({$tag->name})"
                );
            }

            $visited[] = $tag->id;
            $tag = $tag->aliasTag;
        }

        return $tag;
    }

    public function validateNoCircularAlias(Tag $tag, Tag $target): void
    {
        if ($tag->category !== $target->category) {
            throw new \RuntimeException(
                "Cannot alias tag #{$tag->id} ({$tag->name}) to tag #{$target->id} ({$target->name}) because they belong to different categories."
            );
        }
        if ($target->id === $tag->id) {
            throw new \RuntimeException(
                "Cannot alias tag #{$tag->id} ({$tag->name}) to itself."
            );
        }

        $visited = [];
        $current = $target;
        while ($current->alias_tag_id !== null) {
            if (in_array($current->id, $visited)) {
                throw new \RuntimeException(
                    "Circular alias detected in the chain for tag #{$current->id} ({$current->name})"
                );
            }

            $visited[] = $current->id;
            $current = $current->aliasTag;

            if ($current->category !== $tag->category) {
                throw new \RuntimeException(
                    "Cannot alias tag #{$tag->id} ({$tag->name}) to tag #{$target->id} ({$target->name}) because it would create a chain that includes tags from different categories."
                );
            }
            if ($current->id === $tag->id) {
                throw new \RuntimeException(
                    "Cannot alias tag #{$tag->id} ({$tag->name}) to tag #{$target->id} ({$target->name}) because it would create a circular chain."
                );
            }
        }
    }

    public function findOrCreate(string $name, TagCategory $category): Tag
    {
        $name = $this->normalizeTagName($name);

        $tag = Tag::firstOrCreate(
            ['name' => $name, 'category' => $category],
            ['created_by' => Auth::id()]
        );

        return $this->resolveAlias($tag);
    }

    public function syncPostTags(Post $post, array $tags): void
    {
        foreach ($tags as ['name' => $name, 'category' => $category]) {
            if (empty($name)) {
                continue;
            }

            $tag = $this->findOrCreate($name, $category);

            if (!$post->tags()->where('tag_id', $tag->id)->exists()) {
                $post->tags()->attach($tag->id, [
                    'added_by_user_id' => Auth::id(),
                ]);

                $tag->increment('post_count');
            }
        }
    }

    public function parseSearchInput(string $input): array
    {
        $include = [];
        $exclude = [];

        foreach (preg_split('/\s+/', trim($input)) as $token) {
            if (empty($token)) {
                continue;
            }

            $negate = str_starts_with($token, '-');
            $token = $negate ? substr($token, 1) : $token;

            $parsed = $this->parseSingleTag($token);
            if ($parsed === null) {
                continue;
            }

            $key = $parsed['category']->value . ':' . $parsed['name'];

            if ($negate) {
                $exclude[$key] = $parsed;
                unset($include[$key]);
            } else {
                if (!isset($exclude[$key])) {
                    $include[$key] = $parsed;
                }
            }
        }

        return [
            'include' => array_values($include),
            'exclude' => array_values($exclude),
        ];
    }
}
