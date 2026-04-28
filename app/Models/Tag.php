<?php

namespace App\Models;

use App\TagCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'created_by',
        'alias_tag_id',
    ];

    protected function casts(): array
    {
        return [
            'category' => TagCategory::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'name';
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_tags')
            ->withPivot('added_by_user_id')
            ->withTimestamps();
    }

    public function aliasTag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'alias_tag_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function url(): string
    {
        return route('tags.show', ['category' => $this->category, 'tag' => $this]);
    }
}
