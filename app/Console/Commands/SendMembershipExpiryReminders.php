<?php

namespace App\Console\Commands;

use App\Services\MembershipExpiryService;
use Illuminate\Console\Command;

class SendMembershipExpiryReminders extends Command
{
    protected $signature = 'memberships:send-expiry-reminders';

    protected $description = 'Expire ended memberships and send idempotent two-day and one-day reminders';

    public function handle(MembershipExpiryService $memberships): int
    {
        $result = $memberships->process();
        $this->info(sprintf(
            'Expired: %d; two-day reminders: %d; one-day reminders: %d.',
            $result['expired'],
            $result['twoDayReminders'],
            $result['oneDayReminders'],
        ));

        return self::SUCCESS;
    }
}
