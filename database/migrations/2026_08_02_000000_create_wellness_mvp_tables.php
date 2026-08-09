<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_plans', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedSmallInteger('points')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('workout_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workout_plan_id')->constrained()->cascadeOnDelete();
            $table->date('completed_on');
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('points_awarded');
            $table->timestamps();
            $table->unique(
                ['user_id', 'workout_plan_id', 'completed_on'],
                'workout_completion_once_per_day'
            );
        });

        Schema::create('body_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('recorded_on');
            $table->decimal('weight_kg', 5, 2);
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->decimal('chest_cm', 5, 2)->nullable();
            $table->decimal('waist_cm', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'recorded_on'], 'measurement_once_per_day');
        });

        Schema::create('wellness_activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('category', ['meditation', 'breathing', 'lifestyle']);
            $table->text('description');
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedSmallInteger('points')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('wellness_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wellness_activity_id')->constrained()->cascadeOnDelete();
            $table->date('completed_on');
            $table->unsignedSmallInteger('points_awarded');
            $table->timestamps();
            $table->unique(
                ['user_id', 'wellness_activity_id', 'completed_on'],
                'wellness_completion_once_per_day'
            );
        });

        Schema::create('therapy_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->text('symptoms');
            $table->date('preferred_date')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'scheduled', 'completed', 'cancelled'])->default('pending');
            $table->text('practitioner_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('therapy_requests');
        Schema::dropIfExists('wellness_completions');
        Schema::dropIfExists('wellness_activities');
        Schema::dropIfExists('body_measurements');
        Schema::dropIfExists('workout_completions');
        Schema::dropIfExists('workout_plans');
    }
};
