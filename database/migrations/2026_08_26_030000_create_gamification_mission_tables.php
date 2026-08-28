<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gamification_missions', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);
            $table->string('title', 120);
            $table->string('slug', 150)->unique();
            $table->text('description');
            $table->string('metric', 40);
            $table->unsignedInteger('target_value');
            $table->unsignedInteger('reward_xp')->default(0);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'kind'], 'mission_status_kind_index');
            $table->index(['starts_on', 'ends_on'], 'mission_date_window_index');
        });

        Schema::create('member_missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gamification_mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at');
            $table->unsignedInteger('progress_value')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('reward_xp_awarded')->default(0);
            $table->timestamps();

            $table->unique(['gamification_mission_id', 'user_id'], 'member_mission_unique');
            $table->index(['user_id', 'completed_at'], 'member_mission_completion_index');
        });

        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('slug', 150)->unique();
            $table->text('description');
            $table->string('metric', 40);
            $table->unsignedInteger('threshold');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order'], 'achievement_visibility_index');
        });

        Schema::create('member_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('achievement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('progress_value');
            $table->timestamp('unlocked_at');
            $table->timestamps();

            $table->unique(['achievement_id', 'user_id'], 'member_achievement_unique');
            $table->index(['user_id', 'unlocked_at'], 'member_achievement_unlock_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_achievements');
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('member_missions');
        Schema::dropIfExists('gamification_missions');
    }
};
