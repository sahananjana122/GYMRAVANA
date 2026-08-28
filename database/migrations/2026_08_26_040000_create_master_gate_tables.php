<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progression_readiness_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monthly_progress_review_id')
                ->nullable()
                ->constrained('monthly_progress_reviews', indexName: 'readiness_prediction_review_fk')
                ->nullOnDelete();
            $table->string('model_version', 100);
            $table->boolean('predicted_ready');
            $table->decimal('readiness_probability', 6, 5)->nullable();
            $table->json('feature_snapshot');
            $table->json('explanation')->nullable();
            $table->timestamp('predicted_at');
            $table->timestamps();

            $table->index(['user_id', 'predicted_at'], 'readiness_prediction_member_time_index');
            $table->index(['model_version', 'predicted_ready'], 'readiness_prediction_model_result_index');
        });

        Schema::create('master_gate_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('progression_readiness_prediction_id')
                ->nullable()
                ->constrained('progression_readiness_predictions', indexName: 'master_gate_prediction_fk')
                ->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->text('member_statement')->nullable();
            $table->json('eligibility_snapshot');
            $table->timestamp('requested_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->boolean('is_override')->default(false);
            $table->text('override_reason')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'master_gate_member_status_index');
            $table->index(['status', 'requested_at'], 'master_gate_review_queue_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_gate_applications');
        Schema::dropIfExists('progression_readiness_predictions');
    }
};
