<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('number')->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('unlocks_master_gate')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'number'], 'game_level_path_index');
        });

        Schema::create('game_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_level_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('exercise_name', 160);
            $table->string('metric_type', 40);
            $table->decimal('target_value', 10, 2)->default(1);
            $table->decimal('pace_target', 10, 2)->nullable();
            $table->string('pace_unit', 30)->nullable();
            $table->string('validation_method', 40)->default('trainer_review');
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['game_level_id', 'is_active', 'sort_order'], 'game_goal_level_index');
        });

        Schema::create('member_game_goal_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_goal_id')->constrained()->cascadeOnDelete();
            $table->decimal('current_value', 10, 2)->default(0);
            $table->decimal('pace_value', 10, 2)->nullable();
            $table->string('source', 40)->default('trainer');
            $table->json('evidence')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamp('achieved_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'game_goal_id'], 'member_game_goal_unique');
            $table->index(['user_id', 'achieved_at'], 'member_game_goal_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_game_goal_progress');
        Schema::dropIfExists('game_goals');
        Schema::dropIfExists('game_levels');
    }
};
