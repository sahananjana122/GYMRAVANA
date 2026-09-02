<?php

namespace App\Services;

use App\Models\MemberProfile;
use App\Models\MembershipNumberSequence;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class MembershipNumberService
{
    public function assign(MemberProfile $profile, DateTimeInterface $activationDate): string
    {
        return DB::transaction(function () use ($profile, $activationDate): string {
            $lockedProfile = MemberProfile::query()->lockForUpdate()->findOrFail($profile->id);

            if (filled($lockedProfile->membership_number)) {
                return $lockedProfile->membership_number;
            }

            $year = (int) $activationDate->format('Y');
            MembershipNumberSequence::query()->insertOrIgnore([
                'year' => $year,
                'current_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = MembershipNumberSequence::query()
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();
            $nextNumber = $sequence->current_number + 1;
            $sequence->update(['current_number' => $nextNumber]);

            $membershipNumber = sprintf('GR-%04d-%04d', $year, $nextNumber);
            $lockedProfile->update(['membership_number' => $membershipNumber]);
            $profile->membership_number = $membershipNumber;

            return $membershipNumber;
        });
    }
}
