<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('trainer_bookings', 'program_type')) {
            Schema::table('trainer_bookings', function (Blueprint $table) {
                $table->string('program_type')->default('Personal training')->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('trainer_bookings', 'program_type')) {
            Schema::table('trainer_bookings', function (Blueprint $table) {
                $table->dropColumn('program_type');
            });
        }
    }
};
