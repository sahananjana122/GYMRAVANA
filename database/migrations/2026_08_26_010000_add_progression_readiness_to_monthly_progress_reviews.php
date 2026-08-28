<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_progress_reviews', function (Blueprint $table) {
            $table->boolean('ready_for_progression')->nullable()->after('assessment');
            $table->text('readiness_rationale')->nullable()->after('ready_for_progression');
            $table->index(
                ['review_month', 'ready_for_progression'],
                'monthly_review_readiness_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('monthly_progress_reviews', function (Blueprint $table) {
            $table->dropIndex('monthly_review_readiness_index');
            $table->dropColumn(['ready_for_progression', 'readiness_rationale']);
        });
    }
};
