<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainer_bookings', function (Blueprint $table) {
            $table->dateTime('confirmed_start_at')->nullable()->after('requested_datetime');
            $table->unsignedSmallInteger('duration_minutes')->nullable()->after('confirmed_start_at');
            $table->dateTime('required_arrival_at')->nullable()->after('duration_minutes');
            $table->text('preparation_instructions')->nullable()->after('notes');
            $table->text('trainer_message')->nullable()->after('preparation_instructions');
            $table->foreignId('scheduled_by')->nullable()->after('trainer_message')->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable()->after('scheduled_by');

            $table->index(['trainer_profile_id', 'confirmed_start_at'], 'trainer_schedule_start_index');
            $table->index(['status', 'confirmed_start_at'], 'booking_status_start_index');
        });
    }

    public function down(): void
    {
        Schema::table('trainer_bookings', function (Blueprint $table) {
            $table->dropIndex('trainer_schedule_start_index');
            $table->dropIndex('booking_status_start_index');
            $table->dropConstrainedForeignId('scheduled_by');
            $table->dropColumn([
                'confirmed_start_at',
                'duration_minutes',
                'required_arrival_at',
                'preparation_instructions',
                'trainer_message',
                'confirmed_at',
            ]);
        });
    }
};
