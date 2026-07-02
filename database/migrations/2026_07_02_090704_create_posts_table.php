<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_post_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_question')->default(false);
            $table->boolean('is_answer')->default(false);
            $table->boolean('is_relevant')->default(true);
            $table->boolean('is_flood')->default(false);
            $table->float('relevance_score')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
