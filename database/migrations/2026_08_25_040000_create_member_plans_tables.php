<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('supersedes_plan_id')->nullable()->constrained('member_plans')->nullOnDelete();
            $table->enum('type', ['workout', 'meal']);
            $table->string('title');
            $table->text('overview')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['draft', 'active', 'completed', 'archived'])->default('draft');
            $table->unsignedSmallInteger('version')->default(1);
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'status'], 'member_plan_owner_type_status_index');
            $table->index(['trainer_profile_id', 'status'], 'member_plan_trainer_status_index');
        });

        Schema::create('member_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week')->nullable();
            $table->time('scheduled_time')->nullable();
            $table->string('section')->nullable();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->string('target')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['member_plan_id', 'day_of_week', 'display_order'], 'member_plan_item_schedule_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_plan_items');
        Schema::dropIfExists('member_plans');
    }
};
