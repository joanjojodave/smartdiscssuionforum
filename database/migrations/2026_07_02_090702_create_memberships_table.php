<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'active', 'warned', 'blacklisted', 'declined'])->default('pending');
            $table->unsignedTinyInteger('warnings_count')->default(0);
            $table->timestamp('last_active_at')->nullable();
            $table->boolean('agreed_rules')->default(false);
            $table->timestamp('blacklist_until')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
