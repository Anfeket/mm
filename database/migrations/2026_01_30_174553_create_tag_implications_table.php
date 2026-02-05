<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tag_implications', function (Blueprint $table) {
            $table->foreignId('implying_tag_id')->constrained('tags')->cascadeOnDelete();
            $table->foreignId('implied_tag_id')->constrained('tags')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['implying_tag_id', 'implied_tag_id']);
            $table->index('implied_tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tag_implications');
    }
};
