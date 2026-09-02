<?php

namespace App\Console\Commands;

use App\Models\MemberProfile;
use App\Services\MembershipNumberService;
use Illuminate\Console\Command;

class BackfillMembershipNumbers extends Command
{
    protected $signature = 'memberships:backfill-numbers';

    protected $description = 'Assign permanent membership numbers to legacy member profiles that do not have one';

    public function handle(MembershipNumberService $numbers): int
    {
        $profiles = MemberProfile::query()
            ->whereNull('membership_number')
            ->with('user')
            ->orderByRaw('CASE WHEN joined_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('joined_at')
            ->orderBy('id')
            ->get();

        foreach ($profiles as $profile) {
            $numbers->assign(
                $profile,
                $profile->joined_at ?? $profile->user?->created_at ?? now(),
            );
        }

        $this->info($profiles->count().' membership number(s) assigned.');

        return self::SUCCESS;
    }
}
