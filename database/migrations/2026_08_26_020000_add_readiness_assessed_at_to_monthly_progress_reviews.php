<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_progress_reviews', function (Blueprint $table) {
            $table->timestamp('readiness_assessed_at')->nullable()->after('readiness_rationale');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_progress_reviews', function (Blueprint $table) {
            $table->dropColumn('readiness_assessed_at');
        });
    }
};
