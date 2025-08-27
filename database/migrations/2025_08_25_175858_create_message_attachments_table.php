<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type'); // mime type
            $table->bigInteger('file_size'); // in bytes
            $table->string('file_url')->nullable();
            $table->json('metadata')->nullable(); // thumbnails, dimensions, duration, etc.
            $table->timestamps();

            // Indexes
            $table->index('message_id');
            $table->index('user_id');
            $table->index('file_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
