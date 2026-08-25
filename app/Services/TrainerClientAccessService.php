<?php

namespace App\Services;

use App\Models\TrainerBooking;
use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TrainerClientAccessService
{
    public const ASSIGNED_BOOKING_STATUSES = [
        TrainerBooking::STATUS_ACCEPTED,
        TrainerBooking::STATUS_COMPLETED,
    ];

    public function assignedMembersQuery(TrainerProfile $profile): Builder
    {
        return User::query()
            ->role('member')
            ->whereHas('trainerBookings', function (Builder $query) use ($profile): void {
                $query->where('trainer_profile_id', $profile->id)
                    ->whereIn('status', self::ASSIGNED_BOOKING_STATUSES);
            });
    }

    public function canManage(TrainerProfile $profile, User $member): bool
    {
        return $member->hasRole('member')
            && $member->trainerBookings()
                ->where('trainer_profile_id', $profile->id)
                ->whereIn('status', self::ASSIGNED_BOOKING_STATUSES)
                ->exists();
    }

    public function authorize(TrainerProfile $profile, User $member): void
    {
        abort_unless($this->canManage($profile, $member), 403, 'This member is not assigned to your trainer account.');
    }
}
