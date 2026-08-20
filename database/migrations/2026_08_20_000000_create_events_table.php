<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('slug', 140)->unique();
            $table->string('event_type', 40)->default('community');
            $table->string('summary', 255);
            $table->text('description');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('venue', 180);
            $table->unsignedInteger('capacity')->nullable();
            $table->string('image_path', 500)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
