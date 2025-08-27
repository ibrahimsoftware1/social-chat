<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('joined_at');
            $table->boolean('is_admin')->default(false);
            $table->timestamp('last_read_at')->nullable();
            $table->boolean('is_muted')->default(false);
            $table->boolean('notification_enabled')->default(true);
            $table->timestamps();

            // Unique constraint to prevent duplicate entries
            $table->unique(['conversation_id', 'user_id']);

            // Indexes
            $table->index('user_id');
            $table->index('conversation_id');
            $table->index('last_read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_user');
    }
};
