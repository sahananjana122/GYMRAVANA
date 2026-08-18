<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('trainer_profiles', 'gender')) {
            Schema::table('trainer_profiles', function (Blueprint $table) {
                $table->string('gender', 30)->nullable()->after('specialty');
            });
        }

        if (! Schema::hasColumn('trainer_profiles', 'experience_years')) {
            Schema::table('trainer_profiles', function (Blueprint $table) {
                $table->unsignedTinyInteger('experience_years')->default(0)->after('certifications');
            });
        }

        if (! Schema::hasTable('group_programs')) {
            Schema::create('group_programs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('trainer_profile_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description');
                $table->string('schedule_info');
                $table->string('level')->default('All levels');
                $table->unsignedSmallInteger('duration_minutes')->default(45);
                $table->unsignedSmallInteger('capacity')->default(20);
                $table->string('image_path')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('group_program_registrations')) {
            Schema::create('group_program_registrations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_program_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('email');
                $table->string('phone', 30)->nullable();
                $table->string('preferred_session')->nullable();
                $table->text('notes')->nullable();
                $table->enum('status', ['pending', 'confirmed', 'attended', 'cancelled'])->default('pending');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('treatments')) {
            Schema::create('treatments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('therapy_category_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->enum('treatment_type', ['nadi', 'yoga_therapy', 'other']);
                $table->text('description');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('therapy_conditions')) {
            Schema::create('therapy_conditions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('condition_treatment')) {
            Schema::create('condition_treatment', function (Blueprint $table) {
                $table->id();
                $table->foreignId('therapy_condition_id')->constrained()->cascadeOnDelete();
                $table->foreignId('treatment_id')->constrained()->cascadeOnDelete();
                $table->text('rationale')->nullable();
                $table->unsignedTinyInteger('priority')->default(1);
                $table->unique(['therapy_condition_id', 'treatment_id'], 'condition_treatment_unique');
            });
        }

        if (! Schema::hasTable('therapy_specialists')) {
            Schema::create('therapy_specialists', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('gender', 30)->nullable();
                $table->string('specialization');
                $table->text('bio');
                $table->text('qualifications')->nullable();
                $table->unsignedTinyInteger('experience_years')->default(0);
                $table->string('photo_path')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('specialist_treatment')) {
            Schema::create('specialist_treatment', function (Blueprint $table) {
                $table->id();
                $table->foreignId('therapy_specialist_id')->constrained()->cascadeOnDelete();
                $table->foreignId('treatment_id')->constrained()->cascadeOnDelete();
                $table->unique(['therapy_specialist_id', 'treatment_id'], 'specialist_treatment_unique');
            });
        }

        if (! Schema::hasTable('therapy_appointments')) {
            Schema::create('therapy_appointments', function (Blueprint $table) {
                $table->id();
                $table->uuid('appointment_number')->unique();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('therapy_condition_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('treatment_id')->constrained()->restrictOnDelete();
                $table->foreignId('therapy_specialist_id')->constrained()->restrictOnDelete();
                $table->string('customer_name');
                $table->string('contact_email')->nullable();
                $table->string('contact_phone', 30)->nullable();
                $table->dateTime('preferred_datetime');
                $table->text('notes')->nullable();
                $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('contact_enquiries')) {
            Schema::create('contact_enquiries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('email');
                $table->string('phone', 30)->nullable();
                $table->string('subject')->nullable();
                $table->text('message');
                $table->enum('status', ['new', 'in_progress', 'resolved', 'closed'])->default('new');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_enquiries');
        Schema::dropIfExists('therapy_appointments');
        Schema::dropIfExists('specialist_treatment');
        Schema::dropIfExists('therapy_specialists');
        Schema::dropIfExists('condition_treatment');
        Schema::dropIfExists('therapy_conditions');
        Schema::dropIfExists('treatments');
        Schema::dropIfExists('group_program_registrations');
        Schema::dropIfExists('group_programs');

        if (Schema::hasColumn('trainer_profiles', 'gender')) {
            Schema::table('trainer_profiles', function (Blueprint $table) {
                $table->dropColumn('gender');
            });
        }

        if (Schema::hasColumn('trainer_profiles', 'experience_years')) {
            Schema::table('trainer_profiles', function (Blueprint $table) {
                $table->dropColumn('experience_years');
            });
        }
    }
};
