<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\PostProcessingStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_post_id')->nullable()->constrained('posts')->nullOnDelete();

            $table->string('mime_type', 100);
            $table->string('file_hash', 64);
            $table->string('file_path');
            $table->string('thumb_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('file_size');

            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->text('description')->nullable();
            $table->string('source_url', 2048)->nullable();

            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('like_count')->default(0);
            $table->unsignedInteger('favorites_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);

            $table->boolean('is_nsfw')->default(false);
            $table->boolean('is_listed')->default(false);
            $table->string('processing_status', 20)->default(PostProcessingStatus::Processing->value);
            $table->text('processing_error')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('author_id');
            $table->index(['is_listed', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
