<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('photo_consent_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->string('title', 160);
            $table->string('slug', 180)->unique();
            $table->string('summary', 300)->nullable();
            $table->text('body');
            $table->date('highlight_month')->nullable();
            $table->text('progress_summary')->nullable();
            $table->json('public_statistics')->nullable();
            $table->string('cover_image_path', 500)->nullable();
            $table->string('before_image_path', 500)->nullable();
            $table->string('progress_image_path', 500)->nullable();
            $table->string('after_image_path', 500)->nullable();
            $table->boolean('photo_consent_confirmed')->default(false);
            $table->timestamp('photo_consent_confirmed_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'published_at']);
            $table->index(['type', 'highlight_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
