<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->boolean('share_measurements_with_trainer')->default(false)->after('phone');
        });

        Schema::create('monthly_progress_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('review_month');
            $table->text('monthly_goals')->nullable();
            $table->unsignedTinyInteger('goal_completion_percent')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->enum('assessment', ['needs_support', 'on_track', 'excellent'])->nullable();
            $table->text('trainer_notes')->nullable();
            $table->text('next_month_goals')->nullable();
            $table->timestamps();

            $table->unique(
                ['trainer_profile_id', 'user_id', 'review_month'],
                'trainer_member_month_review_unique'
            );
            $table->index(['user_id', 'review_month'], 'member_review_month_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_progress_reviews');

        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropColumn('share_measurements_with_trainer');
        });
    }
};
