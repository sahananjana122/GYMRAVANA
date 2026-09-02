<?php

namespace App\Services;

use App\Models\MemberProfile;
use App\Models\MembershipSubscription;
use App\Notifications\MembershipExpiryNotification;
use Illuminate\Support\Facades\DB;

class MembershipExpiryService
{
    public function process(): array
    {
        $expired = $this->expirePastSubscriptions();
        $twoDayReminders = $this->sendReminders(2);
        $oneDayReminders = $this->sendReminders(1);

        return compact('expired', 'twoDayReminders', 'oneDayReminders');
    }

    private function expirePastSubscriptions(): int
    {
        $expired = 0;
        $ids = MembershipSubscription::query()
            ->active()
            ->whereDate('ends_on', '<', today())
            ->pluck('id');

        foreach ($ids as $id) {
            $changed = DB::transaction(function () use ($id): bool {
                $subscription = MembershipSubscription::query()->lockForUpdate()->find($id);
                if (! $subscription
                    || $subscription->status !== MembershipSubscription::STATUS_ACTIVE
                    || ! $subscription->ends_on?->isBefore(today())) {
                    return false;
                }

                $subscription->update(['status' => MembershipSubscription::STATUS_EXPIRED]);
                $replacement = MembershipSubscription::query()
                    ->where('user_id', $subscription->user_id)
                    ->whereKeyNot($subscription->id)
                    ->active()
                    ->latest('activated_at')
                    ->first();
                $profile = MemberProfile::query()
                    ->where('user_id', $subscription->user_id)
                    ->lockForUpdate()
                    ->first();

                if ($profile) {
                    $profile->update($replacement ? [
                        'membership_tier_id' => $replacement->membership_tier_id,
                        'status' => 'active',
                    ] : [
                        'membership_tier_id' => null,
                        'status' => 'pending',
                    ]);
                }

                return true;
            });

            $expired += $changed ? 1 : 0;
        }

        return $expired;
    }

    private function sendReminders(int $daysRemaining): int
    {
        $sent = 0;
        $column = $daysRemaining === 2
            ? 'two_day_reminder_sent_at'
            : 'one_day_reminder_sent_at';
        $ids = MembershipSubscription::query()
            ->active()
            ->whereDate('ends_on', today()->addDays($daysRemaining))
            ->whereNull($column)
            ->pluck('id');

        foreach ($ids as $id) {
            $subscription = DB::transaction(function () use ($id, $column, $daysRemaining): ?MembershipSubscription {
                $subscription = MembershipSubscription::query()
                    ->with(['member.memberProfile', 'tier'])
                    ->lockForUpdate()
                    ->find($id);
                if (! $subscription
                    || $subscription->status !== MembershipSubscription::STATUS_ACTIVE
                    || $subscription->{$column} !== null
                    || ! $subscription->ends_on?->isSameDay(today()->addDays($daysRemaining))) {
                    return null;
                }

                $hasReplacement = MembershipSubscription::query()
                    ->where('user_id', $subscription->user_id)
                    ->whereKeyNot($subscription->id)
                    ->active()
                    ->exists();
                if ($hasReplacement) {
                    return null;
                }

                $subscription->update([$column => now()]);

                return $subscription;
            });

            if ($subscription) {
                $subscription->member->notify(
                    new MembershipExpiryNotification($subscription, $daysRemaining),
                );
                $sent++;
            }
        }

        return $sent;
    }
}
