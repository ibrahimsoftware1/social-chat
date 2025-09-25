<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('following_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('accepted_at')->nullable(); // For private accounts
            $table->timestamps();

            // Prevent duplicate follows
            $table->unique(['follower_id', 'following_id']);

            // Indexes for performance
            $table->index('follower_id');
            $table->index('following_id');
            $table->index('accepted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
