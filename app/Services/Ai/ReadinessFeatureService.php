<?php

namespace App\Services\Ai;

use App\Models\MonthlyProgressReview;
use App\Models\TrainerBooking;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use LogicException;

class ReadinessFeatureService
{
    public const FEATURES = [
        'workout_completions',
        'wellness_completions',
        'trainer_sessions_scheduled',
        'trainer_sessions_completed',
        'attendance_rate',
        'cancelled_or_declined_sessions',
        'active_days',
        'consistency_rate',
        'activity_points',
        'previous_goal_completion',
        'previous_rating',
        'workout_change',
        'consistency_change',
        'previous_assessment',
    ];

    public function forReview(MonthlyProgressReview $review): array
    {
        $review->loadMissing('member');

        if (! $review->member || ! $review->readiness_assessed_at) {
            throw new LogicException('A readiness feature snapshot requires a member and assessment time.');
        }

        return $this->snapshot(
            $review->member,
            $review->trainer_profile_id,
            $review->readiness_assessed_at,
            $review->review_month,
        );
    }

    public function currentFor(User $member, int $trainerProfileId, ?Carbon $observedAt = null): array
    {
        $observedAt ??= now();

        return $this->snapshot($member, $trainerProfileId, $observedAt, $observedAt);
    }

    private function snapshot(
        User $member,
        int $trainerProfileId,
        Carbon $observedAt,
        Carbon $observationMonth,
    ): array {
        $monthStart = $observationMonth->copy()->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $cutoff = $observedAt->copy()->min($monthEnd);
        $current = $this->metrics($member, $trainerProfileId, $monthStart, $cutoff);

        $previousStart = $monthStart->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $previousStart->copy()->endOfMonth();
        $previous = $this->metrics($member, $trainerProfileId, $previousStart, $previousEnd);
        $previousReview = MonthlyProgressReview::query()
            ->where('trainer_profile_id', $trainerProfileId)
            ->where('user_id', $member->id)
            ->whereDate('review_month', $previousStart->toDateString())
            ->where('updated_at', '<=', $observedAt)
            ->first();

        return [
            'workout_completions' => $current['workouts'],
            'wellness_completions' => $current['wellness'],
            'trainer_sessions_scheduled' => $current['sessions_scheduled'],
            'trainer_sessions_completed' => $current['sessions_completed'],
            'attendance_rate' => $current['attendance_rate'],
            'cancelled_or_declined_sessions' => $current['cancelled_or_declined'],
            'active_days' => $current['active_days'],
            'consistency_rate' => $current['consistency_rate'],
            'activity_points' => $current['points'],
            'previous_goal_completion' => $previousReview?->goal_completion_percent,
            'previous_rating' => $previousReview?->rating,
            'workout_change' => $current['workouts'] - $previous['workouts'],
            'consistency_change' => round($current['consistency_rate'] - $previous['consistency_rate'], 4),
            'previous_assessment' => $previousReview?->assessment,
        ];
    }

    private function metrics(User $member, int $trainerProfileId, Carbon $start, Carbon $cutoff): array
    {
        $startDate = $start->toDateString();
        $cutoffDate = $cutoff->toDateString();
        $workouts = $member->workoutCompletions()
            ->whereBetween('completed_on', [$startDate, $cutoffDate])
            ->get();
        $wellness = $member->wellnessCompletions()
            ->whereBetween('completed_on', [$startDate, $cutoffDate])
            ->get();
        $scheduledSessions = $member->trainerBookings()
            ->where('trainer_profile_id', $trainerProfileId)
            ->whereIn('status', [TrainerBooking::STATUS_ACCEPTED, TrainerBooking::STATUS_COMPLETED])
            ->whereBetween('confirmed_start_at', [$start, $cutoff])
            ->get();
        $completedSessions = $scheduledSessions->where('status', TrainerBooking::STATUS_COMPLETED);
        $cancelledOrDeclined = $member->trainerBookings()
            ->where('trainer_profile_id', $trainerProfileId)
            ->whereIn('status', [TrainerBooking::STATUS_CANCELLED, TrainerBooking::STATUS_DECLINED])
            ->where(function ($query) use ($start, $cutoff): void {
                $query->whereBetween('confirmed_start_at', [$start, $cutoff])
                    ->orWhere(function ($query) use ($start, $cutoff): void {
                        $query->whereNull('confirmed_start_at')
                            ->whereBetween('requested_datetime', [$start, $cutoff]);
                    });
            })
            ->count();
        $activeDays = $this->activeDays($workouts, $wellness, $completedSessions);
        $daysConsidered = max(1, (int) $start->diffInDays($cutoff) + 1);

        return [
            'workouts' => $workouts->count(),
            'wellness' => $wellness->count(),
            'sessions_scheduled' => $scheduledSessions->count(),
            'sessions_completed' => $completedSessions->count(),
            'attendance_rate' => $scheduledSessions->isEmpty()
                ? null
                : round($completedSessions->count() / $scheduledSessions->count(), 4),
            'cancelled_or_declined' => $cancelledOrDeclined,
            'active_days' => $activeDays,
            'consistency_rate' => round($activeDays / $daysConsidered, 4),
            'points' => (int) $workouts->sum('points_awarded') + (int) $wellness->sum('points_awarded'),
        ];
    }

    private function activeDays(Collection $workouts, Collection $wellness, Collection $sessions): int
    {
        return $workouts->pluck('completed_on')
            ->merge($wellness->pluck('completed_on'))
            ->merge($sessions->pluck('confirmed_start_at'))
            ->filter()
            ->map(fn ($date) => $date->toDateString())
            ->unique()
            ->count();
    }
}
