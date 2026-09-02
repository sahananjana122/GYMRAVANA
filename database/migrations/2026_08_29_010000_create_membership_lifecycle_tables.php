<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_tiers', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_months')->default(1)->after('billing_period');
        });

        Schema::table('member_profiles', function (Blueprint $table) {
            $table->string('membership_number', 20)->nullable()->unique()->after('user_id');
        });

        Schema::create('membership_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->unsignedInteger('current_number')->default(0);
            $table->timestamps();
        });

        Schema::create('membership_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_tier_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('pending');
            $table->decimal('amount_snapshot', 10, 2);
            $table->unsignedSmallInteger('duration_months');
            $table->boolean('is_initial')->default(false);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('registration_notification_sent_at')->nullable();
            $table->timestamp('two_day_reminder_sent_at')->nullable();
            $table->timestamp('one_day_reminder_sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'membership_subscription_user_status');
            $table->index(['status', 'ends_on'], 'membership_subscription_expiry');
        });

        Schema::create('membership_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_subscription_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('status', 30)->default('pending');
            $table->string('payment_method', 60)->default('development_mock');
            $table->string('reference_number', 120)->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'paid_at'], 'membership_payment_member_date');
            $table->index(['status', 'paid_at'], 'membership_payment_status_date');
        });

        $profiles = DB::table('member_profiles')
            ->leftJoin('users', 'users.id', '=', 'member_profiles.user_id')
            ->select([
                'member_profiles.id',
                'member_profiles.joined_at',
                'users.created_at as user_created_at',
            ])
            ->whereNull('member_profiles.membership_number')
            ->get()
            ->sortBy(fn (object $profile): string => sprintf(
                '%s-%020d',
                $profile->joined_at ?? $profile->user_created_at ?? now()->toDateTimeString(),
                $profile->id,
            ));

        $sequences = [];
        foreach ($profiles as $profile) {
            $activationDate = new DateTimeImmutable($profile->joined_at ?? $profile->user_created_at ?? 'now');
            $year = (int) $activationDate->format('Y');
            $sequences[$year] = ($sequences[$year] ?? 0) + 1;

            DB::table('member_profiles')->where('id', $profile->id)->update([
                'membership_number' => sprintf('GR-%04d-%04d', $year, $sequences[$year]),
            ]);
        }

        foreach ($sequences as $year => $currentNumber) {
            DB::table('membership_number_sequences')->insert([
                'year' => $year,
                'current_number' => $currentNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_payments');
        Schema::dropIfExists('membership_subscriptions');
        Schema::dropIfExists('membership_number_sequences');

        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropUnique(['membership_number']);
            $table->dropColumn('membership_number');
        });

        Schema::table('membership_tiers', function (Blueprint $table) {
            $table->dropColumn('duration_months');
        });
    }
};
