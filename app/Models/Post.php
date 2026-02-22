<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

use App\PostProcessingStatus;

/**
 * @property PostProcessingStatus $processing_status
 */
class Post extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'author_id',
        'file_path',
        'file_hash',
        'file_size',
        'mime_type',
        'original_filename',
        'width',
        'height',
        'duration_ms',
        'description',
        'source_url',
        'is_listed',
        'processing_status',
    ];

    protected function casts(): array
    {
        return [
            'processing_status' => PostProcessingStatus::class,
        ];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tags')
            ->withPivot('added_by_user_id')
            ->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function isImage()
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isVideo()
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    public function upvotes(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->votes()->where('value', 1)->count(),
        )->shouldCache();
    }

    public function downvotes(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->votes()->where('value', -1)->count(),
        )->shouldCache();
    }
}
