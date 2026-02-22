<?php

namespace App\Models;

use App\TagCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description'
    ];

    protected function casts(): array
    {
        return [
            'category' => TagCategory::class,
        ];
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_tags')
            ->withPivot('added_by_user_id')
            ->withTimestamps();
    }

    public function aliasTag()
    {
        return $this->belongsTo(Tag::class, 'alias_tag_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
