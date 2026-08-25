<?php

namespace App\Services;

use App\Models\MemberPlan;
use App\Models\TherapyAppointment;
use App\Models\TrainerBooking;
use App\Models\User;
use App\Models\WorkoutPlan;
use Illuminate\Support\Collection;

class MemberDashboardService
{
    public function __construct(private readonly ExternalLibraryService $library) {}

    public function dataFor(User $user): array
    {
        $user->load(['memberProfile.membershipTier', 'enrolledServices.category']);
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

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

        return [
            'user' => $user,
            'totalPoints' => $user->totalPoints(),
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
                'label' => $monthStart->format('F Y'),
                'workouts' => $workoutCompletions->count(),
                'wellness' => $wellnessCompletions->count(),
                'trainer_sessions' => $completedTrainerSessions->count(),
                'therapy_sessions' => $completedTherapySessions->count(),
                'points' => (int) $workoutCompletions->sum('points_awarded')
                    + (int) $wellnessCompletions->sum('points_awarded'),
                'active_days' => $this->activeDays(
                    $workoutCompletions,
                    $wellnessCompletions,
                    $completedTrainerSessions,
                    $completedTherapySessions,
                ),
                'weight_change' => $this->weightChange($measurements),
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

    private function weightChange(Collection $measurements): ?float
    {
        if ($measurements->count() < 2
            || $measurements->first()->weight_kg === null
            || $measurements->last()->weight_kg === null) {
            return null;
        }

        return round((float) $measurements->last()->weight_kg - (float) $measurements->first()->weight_kg, 2);
    }
}
