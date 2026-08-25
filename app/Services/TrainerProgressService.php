<?php

namespace App\Services;

use App\Models\TrainerBooking;
use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TrainerProgressService
{
    public function summary(TrainerProfile $profile, User $member, Carbon $month): array
    {
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        $workouts = $member->workoutCompletions()
            ->whereBetween('completed_on', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get();
        $wellness = $member->wellnessCompletions()
            ->whereBetween('completed_on', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get();
        $sessions = $member->trainerBookings()
            ->where('trainer_profile_id', $profile->id)
            ->whereIn('status', [TrainerBooking::STATUS_ACCEPTED, TrainerBooking::STATUS_COMPLETED])
            ->whereBetween('confirmed_start_at', [$monthStart, $monthEnd])
            ->get();
        $completedSessions = $sessions->where('status', TrainerBooking::STATUS_COMPLETED);
        $occurredSessions = $sessions->filter(fn (TrainerBooking $booking): bool => $booking->status === TrainerBooking::STATUS_COMPLETED
            || ($booking->confirmed_start_at && $booking->confirmed_start_at->isPast()));
        $activeDays = $workouts->pluck('completed_on')
            ->merge($wellness->pluck('completed_on'))
            ->merge($completedSessions->pluck('confirmed_start_at'))
            ->filter()
            ->map(fn ($date) => $date->toDateString())
            ->unique()
            ->count();
        $daysConsidered = $monthStart->isSameMonth(today())
            ? max(1, today()->day)
            : $monthStart->daysInMonth;
        $measurementsShared = (bool) $member->memberProfile?->share_measurements_with_trainer;
        $measurements = $measurementsShared
            ? $member->bodyMeasurements()
                ->whereBetween('recorded_on', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->orderBy('recorded_on')
                ->get()
            : collect();

        return [
            'workouts' => $workouts->count(),
            'wellness' => $wellness->count(),
            'sessions_scheduled' => $sessions->count(),
            'sessions_completed' => $completedSessions->count(),
            'attendance_percent' => $occurredSessions->isEmpty()
                ? null
                : (int) round(($completedSessions->count() / $occurredSessions->count()) * 100),
            'points' => (int) $workouts->sum('points_awarded') + (int) $wellness->sum('points_awarded'),
            'active_days' => $activeDays,
            'consistency_percent' => min(100, (int) round(($activeDays / $daysConsidered) * 100)),
            'measurements_shared' => $measurementsShared,
            'measurement_count' => $measurements->count(),
            'weight_change' => $this->change($measurements, 'weight_kg'),
            'waist_change' => $this->change($measurements, 'waist_cm'),
            'review' => $profile->monthlyProgressReviews()
                ->where('user_id', $member->id)
                ->whereDate('review_month', $monthStart->toDateString())
                ->first(),
        ];
    }

    private function change(Collection $measurements, string $field): ?float
    {
        $values = $measurements->pluck($field)->filter(fn ($value) => $value !== null)->values();

        if ($values->count() < 2) {
            return null;
        }

        return round((float) $values->last() - (float) $values->first(), 2);
    }
}
