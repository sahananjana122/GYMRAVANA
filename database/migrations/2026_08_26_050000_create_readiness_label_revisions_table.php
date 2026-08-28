<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('readiness_label_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_progress_review_id')
                ->constrained('monthly_progress_reviews', indexName: 'readiness_revision_review_fk')
                ->cascadeOnDelete();
            $table->foreignId('trainer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_type', 20);
            $table->boolean('previous_label')->nullable();
            $table->boolean('new_label')->nullable();
            $table->text('previous_rationale')->nullable();
            $table->text('new_rationale')->nullable();
            $table->timestamp('changed_at');

            $table->index(['user_id', 'changed_at'], 'readiness_revision_member_time_index');
            $table->index(['trainer_profile_id', 'changed_at'], 'readiness_revision_trainer_time_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('readiness_label_revisions');
    }
};
