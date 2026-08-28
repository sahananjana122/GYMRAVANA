<?php

namespace App\Services;

use App\Models\MemberPlan;
use App\Models\TherapyAppointment;
use App\Models\TrainerBooking;
use App\Models\User;
use App\Models\WorkoutPlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MemberDashboardService
{
    public function __construct(private readonly ExternalLibraryService $library) {}

    public function dataFor(User $user, ?Carbon $month = null): array
    {
        $user->load(['memberProfile.membershipTier', 'enrolledServices.category']);
        $monthStart = ($month ?? now())->copy()->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $workoutCompletions = $user->workoutCompletions()
            ->whereBetween('completed_on', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get();
        $wellnessCompletions = $user->wellnessCompletions()
            ->whereBetween('completed_on', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get();
        $completedTrainerSessions = $user->trainerBookings()
            ->where('status', TrainerBooking::STATUS_COMPLETED)
            ->whereBetween('confirmed_start_at', [$monthStart, $monthEnd])
            ->get();
        $completedTherapySessions = $user->therapyAppointments()
            ->where('status', TherapyAppointment::STATUS_COMPLETED)
            ->whereBetween('confirmed_start_at', [$monthStart, $monthEnd])
            ->get();
        $measurements = $user->bodyMeasurements()
            ->whereBetween('recorded_on', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('recorded_on')
            ->get();
        $activeDays = $this->activeDays(
            $workoutCompletions,
            $wellnessCompletions,
            $completedTrainerSessions,
            $completedTherapySessions,
        );
        $daysConsidered = $monthStart->isSameMonth(today())
            ? max(1, today()->day)
            : $monthStart->daysInMonth;

        return [
            'user' => $user,
            'upcomingTrainerSessions' => $user->trainerBookings()
                ->with('trainerProfile.user')
                ->upcoming()
                ->orderBy('confirmed_start_at')
                ->limit(6)
                ->get(),
            'upcomingTherapySessions' => $user->therapyAppointments()
                ->with(['specialist', 'treatment'])
                ->upcoming()
                ->orderBy('confirmed_start_at')
                ->limit(6)
                ->get(),
            'currentWorkoutPlan' => $this->currentPlan($user, MemberPlan::TYPE_WORKOUT),
            'currentMealPlan' => $this->currentPlan($user, MemberPlan::TYPE_MEAL),
            'recentPlanChanges' => $user->memberPlans()
                ->visibleToMember()
                ->with('trainerProfile.user')
                ->latest('updated_at')
                ->limit(5)
                ->get(),
            'monthlyProgress' => [
                'key' => $monthStart->format('Y-m'),
                'label' => $monthStart->format('F Y'),
                'previous_month' => $monthStart->copy()->subMonthNoOverflow()->format('Y-m'),
                'next_month' => $monthStart->isBefore(today()->startOfMonth())
                    ? $monthStart->copy()->addMonthNoOverflow()->format('Y-m')
                    : null,
                'workouts' => $workoutCompletions->count(),
                'wellness' => $wellnessCompletions->count(),
                'trainer_sessions' => $completedTrainerSessions->count(),
                'therapy_sessions' => $completedTherapySessions->count(),
                'points' => (int) $workoutCompletions->sum('points_awarded')
                    + (int) $wellnessCompletions->sum('points_awarded'),
                'active_days' => $activeDays,
                'days_considered' => $daysConsidered,
                'consistency_percent' => min(100, (int) round(($activeDays / $daysConsidered) * 100)),
                'measurements' => $measurements,
                'weight_change' => $this->measurementChange($measurements, 'weight_kg'),
                'waist_change' => $this->measurementChange($measurements, 'waist_cm'),
                'latest_measurement_date' => $measurements->last()?->recorded_on,
            ],
            'library' => $this->library->details(),
            'availableWorkoutCount' => WorkoutPlan::where('is_active', true)->count(),
        ];
    }

    private function currentPlan(User $user, string $type): ?MemberPlan
    {
        return $user->memberPlans()
            ->current()
            ->where('type', $type)
            ->with(['items', 'trainerProfile.user'])
            ->latest('assigned_at')
            ->latest('id')
            ->first();
    }

    private function activeDays(
        Collection $workouts,
        Collection $wellness,
        Collection $trainerSessions,
        Collection $therapySessions,
    ): int {
        return $workouts->pluck('completed_on')
            ->merge($wellness->pluck('completed_on'))
            ->merge($trainerSessions->pluck('confirmed_start_at'))
            ->merge($therapySessions->pluck('confirmed_start_at'))
            ->filter()
            ->map(fn ($date) => $date->toDateString())
            ->unique()
            ->count();
    }

    private function measurementChange(Collection $measurements, string $field): ?float
    {
        $values = $measurements->pluck($field)
            ->filter(fn ($value) => $value !== null)
            ->values();

        if ($values->count() < 2) {
            return null;
        }

        return round((float) $values->last() - (float) $values->first(), 2);
    }
}
