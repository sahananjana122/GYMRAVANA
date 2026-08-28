<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progression_readiness_predictions', function (Blueprint $table) {
            $table->date('observation_month')->nullable()->after('monthly_progress_review_id');
            $table->char('input_fingerprint', 64)->nullable()->after('feature_snapshot');
            $table->unique(
                ['user_id', 'model_version', 'observation_month', 'input_fingerprint'],
                'readiness_prediction_idempotency_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('progression_readiness_predictions', function (Blueprint $table) {
            $table->dropUnique('readiness_prediction_idempotency_unique');
            $table->dropColumn(['observation_month', 'input_fingerprint']);
        });
    }
};
