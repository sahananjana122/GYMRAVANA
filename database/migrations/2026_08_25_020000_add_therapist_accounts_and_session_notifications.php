<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('therapy_specialists', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('therapy_appointments', function (Blueprint $table) {
            $table->dateTime('confirmed_start_at')->nullable()->after('preferred_datetime');
            $table->unsignedSmallInteger('duration_minutes')->nullable()->after('confirmed_start_at');
            $table->dateTime('required_arrival_at')->nullable()->after('duration_minutes');
            $table->text('preparation_instructions')->nullable()->after('notes');
            $table->text('specialist_message')->nullable()->after('preparation_instructions');
            $table->foreignId('scheduled_by')->nullable()->after('specialist_message')->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable()->after('scheduled_by');
            $table->timestamp('last_reminder_sent_at')->nullable()->after('confirmed_at');
            $table->unsignedSmallInteger('reminder_count')->default(0)->after('last_reminder_sent_at');

            $table->index(['therapy_specialist_id', 'confirmed_start_at'], 'therapy_specialist_start_index');
            $table->index(['status', 'confirmed_start_at'], 'therapy_status_start_index');
        });

        Schema::table('trainer_bookings', function (Blueprint $table) {
            $table->timestamp('last_reminder_sent_at')->nullable()->after('confirmed_at');
            $table->unsignedSmallInteger('reminder_count')->default(0)->after('last_reminder_sent_at');
        });

        Schema::table('member_profiles', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('membership_tier_id');
        });
    }

    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropColumn('phone');
        });

        Schema::table('trainer_bookings', function (Blueprint $table) {
            $table->dropColumn(['last_reminder_sent_at', 'reminder_count']);
        });

        Schema::table('therapy_appointments', function (Blueprint $table) {
            $table->dropIndex('therapy_specialist_start_index');
            $table->dropIndex('therapy_status_start_index');
            $table->dropConstrainedForeignId('scheduled_by');
            $table->dropColumn([
                'confirmed_start_at',
                'duration_minutes',
                'required_arrival_at',
                'preparation_instructions',
                'specialist_message',
                'confirmed_at',
                'last_reminder_sent_at',
                'reminder_count',
            ]);
        });

        Schema::table('therapy_specialists', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
