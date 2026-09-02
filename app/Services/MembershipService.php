<?php

namespace App\Services;

use App\Models\MemberProfile;
use App\Models\MembershipPayment;
use App\Models\MembershipSubscription;
use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MembershipService
{
    public function __construct(
        private readonly MembershipNumberService $membershipNumbers,
        private readonly FinanceLedgerService $finance,
    ) {}

    public function createPendingSubscription(
        User $member,
        MembershipTier $tier,
        bool $isInitial,
    ): MembershipSubscription {
        return DB::transaction(function () use ($member, $tier, $isInitial): MembershipSubscription {
            $tier = MembershipTier::query()->whereKey($tier->id)->where('is_active', true)->firstOrFail();
            $existing = MembershipSubscription::query()
                ->where('user_id', $member->id)
                ->where('membership_tier_id', $tier->id)
                ->where('is_initial', $isInitial)
                ->where('status', MembershipSubscription::STATUS_PENDING)
                ->whereHas('payment', fn ($query) => $query->where('status', MembershipPayment::STATUS_PENDING))
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existing) {
                return $existing->load(['tier', 'payment']);
            }

            $subscription = MembershipSubscription::create([
                'user_id' => $member->id,
                'membership_tier_id' => $tier->id,
                'status' => MembershipSubscription::STATUS_PENDING,
                'amount_snapshot' => $tier->price,
                'duration_months' => max(1, (int) $tier->duration_months),
                'is_initial' => $isInitial,
            ]);
            $subscription->payment()->create([
                'user_id' => $member->id,
                'amount' => $tier->price,
                'status' => MembershipPayment::STATUS_PENDING,
                'payment_method' => 'development_mock',
                'reference_number' => 'GRPAY-'.Str::upper((string) Str::uuid()),
            ]);

            return $subscription->load(['tier', 'payment']);
        });
    }

    public function completeDevelopmentPayment(
        User $member,
        MembershipSubscription $subscription,
    ): MembershipSubscription {
        $result = DB::transaction(function () use ($member, $subscription): array {
            $subscription = MembershipSubscription::query()
                ->whereKey($subscription->id)
                ->where('user_id', $member->id)
                ->with('tier')
                ->lockForUpdate()
                ->firstOrFail();
            $payment = $subscription->payment()->lockForUpdate()->firstOrFail();

            if ($payment->status === MembershipPayment::STATUS_PAID
                && $subscription->status === MembershipSubscription::STATUS_ACTIVE) {
                $this->finance->syncMembershipPayment($payment);

                return [$subscription, false];
            }

            if ($subscription->status !== MembershipSubscription::STATUS_PENDING
                || $payment->status !== MembershipPayment::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'membership' => 'This membership payment can no longer be completed.',
                ]);
            }

            $profile = MemberProfile::query()
                ->where('user_id', $member->id)
                ->lockForUpdate()
                ->firstOrFail();
            MembershipSubscription::query()
                ->where('user_id', $member->id)
                ->whereKeyNot($subscription->id)
                ->where('status', MembershipSubscription::STATUS_ACTIVE)
                ->update([
                    'status' => MembershipSubscription::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                ]);

            $startsOn = today();
            $endsOn = $startsOn->copy()
                ->addMonthsNoOverflow(max(1, $subscription->duration_months))
                ->subDay();
            $joinedAt = $profile->joined_at ?? $startsOn;

            $payment->update([
                'status' => MembershipPayment::STATUS_PAID,
                'amount' => $subscription->amount_snapshot,
                'paid_at' => now(),
            ]);
            $subscription->update([
                'status' => MembershipSubscription::STATUS_ACTIVE,
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'activated_at' => now(),
                'cancelled_at' => null,
            ]);
            $profile->update([
                'membership_tier_id' => $subscription->membership_tier_id,
                'joined_at' => $joinedAt,
                'status' => 'active',
            ]);
            $this->membershipNumbers->assign($profile, $joinedAt);
            $this->finance->syncMembershipPayment($payment->fresh());

            $sendRegistrationNotification = $subscription->is_initial
                && $subscription->registration_notification_sent_at === null;
            if ($sendRegistrationNotification) {
                $subscription->update(['registration_notification_sent_at' => now()]);
            }

            return [$subscription->fresh(['tier', 'payment']), $sendRegistrationNotification];
        });

        /** @var MembershipSubscription $completed */
        [$completed, $sendRegistrationNotification] = $result;
        if ($sendRegistrationNotification) {
            event(new Registered($member->fresh()));
        }

        return $completed;
    }
}
