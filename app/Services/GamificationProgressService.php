<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\GamificationMission;
use App\Models\MemberAchievement;
use App\Models\MemberMission;
use App\Models\TrainerBooking;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GamificationProgressService
{
    public function __construct(private GamificationService $gamification) {}

    public function syncFor(User $user): void
    {
        if (! $user->hasRole('member')) {
            return;
        }

        DB::transaction(function () use ($user): void {
            $participations = MemberMission::query()
                ->where('user_id', $user->id)
                ->whereNull('completed_at')
                ->with('mission')
                ->lockForUpdate()
                ->get();

            foreach ($participations as $participation) {
                $mission = $participation->mission;

                if (! $mission || $mission->status !== GamificationMission::STATUS_PUBLISHED) {
                    continue;
                }

                $progress = $this->missionProgress($user, $mission, $participation);
                $changes = ['progress_value' => $progress];

                if ($progress >= $mission->target_value) {
                    $changes['completed_at'] = now();
                    $changes['reward_xp_awarded'] = $mission->reward_xp;
                }

                $participation->update($changes);
            }
        });

        $this->syncAchievements($user);
    }

    public function overviewFor(User $user): array
    {
        $this->syncFor($user);

        $missions = GamificationMission::query()
            ->where(function (Builder $query) use ($user): void {
                $query->where('status', GamificationMission::STATUS_PUBLISHED)
                    ->orWhereHas('participations', fn (Builder $participations) => $participations->where('user_id', $user->id));
            })
            ->with(['participations' => fn ($query) => $query->where('user_id', $user->id)])
            ->orderByRaw("CASE WHEN kind = 'challenge' THEN 0 ELSE 1 END")
            ->orderBy('ends_on')
            ->orderBy('title')
            ->get()
            ->map(function (GamificationMission $mission): array {
                $participation = $mission->participations->first();
                $progress = $participation?->progress_value ?? 0;
                $completed = $participation?->completed_at !== null;
                $state = match (true) {
                    $completed => 'completed',
                    $participation !== null => 'joined',
                    $mission->starts_on?->isAfter(today()) => 'upcoming',
                    $mission->ends_on?->isBefore(today()) => 'expired',
                    $mission->isJoinable() => 'available',
                    default => 'closed',
                };

                return [
                    'mission' => $mission,
                    'participation' => $participation,
                    'progress' => $progress,
                    'percent' => min(100, (int) floor(($progress / max(1, $mission->target_value)) * 100)),
                    'completed' => $completed,
                    'state' => $state,
                    'can_join' => $participation === null && $mission->isJoinable(),
                ];
            });

        $achievements = Achievement::query()
            ->where(function (Builder $query) use ($user): void {
                $query->where('is_active', true)
                    ->orWhereHas('unlocks', fn (Builder $unlocks) => $unlocks->where('user_id', $user->id));
            })
            ->with(['unlocks' => fn ($query) => $query->where('user_id', $user->id)])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(function (Achievement $achievement) use ($user): array {
                $unlock = $achievement->unlocks->first();
                $progress = $unlock?->progress_value
                    ?? $this->metricValue($user, $achievement->metric);

                return [
                    'achievement' => $achievement,
                    'unlock' => $unlock,
                    'progress' => $progress,
                    'percent' => min(100, (int) floor(($progress / max(1, $achievement->threshold)) * 100)),
                    'unlocked' => $unlock !== null,
                ];
            });

        return [
            'gamification' => $this->gamification->summaryFor($user),
            'missions' => $missions,
            'achievements' => $achievements,
            'joinedMissionCount' => $missions->whereNotNull('participation')->count(),
            'completedMissionCount' => $missions->where('completed', true)->count(),
            'unlockedAchievementCount' => $achievements->where('unlocked', true)->count(),
        ];
    }

    public function metricValue(
        User $user,
        string $metric,
        ?Carbon $startsAt = null,
        ?Carbon $endsAt = null,
        ?Carbon $recordedAfter = null,
    ): int {
        if ($startsAt && $endsAt && $startsAt->isAfter($endsAt)) {
            return 0;
        }

        return match ($metric) {
            GamificationMission::METRIC_WORKOUTS => $this->workoutCount($user, $startsAt, $endsAt, $recordedAfter),
            GamificationMission::METRIC_WELLNESS => $this->wellnessCount($user, $startsAt, $endsAt, $recordedAfter),
            GamificationMission::METRIC_TRAINER_SESSIONS => $this->trainerSessionCount($user, $startsAt, $endsAt, $recordedAfter),
            GamificationMission::METRIC_ACTIVE_DAYS => $this->activityStreaks($user, $startsAt, $endsAt, $recordedAfter)['active_day_count'],
            GamificationMission::METRIC_LONGEST_STREAK => $this->activityStreaks($user, $startsAt, $endsAt, $recordedAfter)['longest'],
            Achievement::METRIC_TOTAL_XP => $this->gamification->summaryFor($user)['total_xp'],
            Achievement::METRIC_LEVEL => $this->gamification->summaryFor($user)['level'],
            default => 0,
        };
    }

    private function missionProgress(User $user, GamificationMission $mission, MemberMission $participation): int
    {
        $recordedAfter = $participation->joined_at->copy();
        $startsAt = $recordedAfter->copy();

        if ($mission->starts_on && $mission->starts_on->startOfDay()->isAfter($startsAt)) {
            $startsAt = $mission->starts_on->copy()->startOfDay();
        }

        $endsAt = now();
        if ($mission->ends_on && $mission->ends_on->endOfDay()->isBefore($endsAt)) {
            $endsAt = $mission->ends_on->copy()->endOfDay();
        }

        return min(
            $mission->target_value,
            $this->metricValue($user, $mission->metric, $startsAt, $endsAt, $recordedAfter),
        );
    }

    private function syncAchievements(User $user): void
    {
        foreach (Achievement::query()->where('is_active', true)->orderBy('id')->get() as $achievement) {
            if ($user->memberAchievements()->where('achievement_id', $achievement->id)->exists()) {
                continue;
            }

            $progress = $this->metricValue($user, $achievement->metric);

            if ($progress >= $achievement->threshold) {
                MemberAchievement::firstOrCreate(
                    ['achievement_id' => $achievement->id, 'user_id' => $user->id],
                    ['progress_value' => $progress, 'unlocked_at' => now()],
                );
            }
        }
    }

    private function workoutCount(User $user, ?Carbon $startsAt, ?Carbon $endsAt, ?Carbon $recordedAfter): int
    {
        return $user->workoutCompletions()
            ->when($recordedAfter, fn ($query, Carbon $date) => $query->where('created_at', '>=', $date))
            ->when($startsAt, fn ($query, Carbon $date) => $query->whereDate('completed_on', '>=', $date->toDateString()))
            ->when($endsAt, fn ($query, Carbon $date) => $query->whereDate('completed_on', '<=', $date->toDateString()))
            ->count();
    }

    private function wellnessCount(User $user, ?Carbon $startsAt, ?Carbon $endsAt, ?Carbon $recordedAfter): int
    {
        return $user->wellnessCompletions()
            ->when($recordedAfter, fn ($query, Carbon $date) => $query->where('created_at', '>=', $date))
            ->when($startsAt, fn ($query, Carbon $date) => $query->whereDate('completed_on', '>=', $date->toDateString()))
            ->when($endsAt, fn ($query, Carbon $date) => $query->whereDate('completed_on', '<=', $date->toDateString()))
            ->count();
    }

    private function trainerSessionCount(User $user, ?Carbon $startsAt, ?Carbon $endsAt, ?Carbon $recordedAfter): int
    {
        return $this->trainerSessionQuery($user, $startsAt, $endsAt, $recordedAfter)->count();
    }

    private function activityStreaks(User $user, ?Carbon $startsAt, ?Carbon $endsAt, ?Carbon $recordedAfter): array
    {
        $workoutDates = $user->workoutCompletions()
            ->when($recordedAfter, fn ($query, Carbon $date) => $query->where('created_at', '>=', $date))
            ->when($startsAt, fn ($query, Carbon $date) => $query->whereDate('completed_on', '>=', $date->toDateString()))
            ->when($endsAt, fn ($query, Carbon $date) => $query->whereDate('completed_on', '<=', $date->toDateString()))
            ->pluck('completed_on');
        $wellnessDates = $user->wellnessCompletions()
            ->when($recordedAfter, fn ($query, Carbon $date) => $query->where('created_at', '>=', $date))
            ->when($startsAt, fn ($query, Carbon $date) => $query->whereDate('completed_on', '>=', $date->toDateString()))
            ->when($endsAt, fn ($query, Carbon $date) => $query->whereDate('completed_on', '<=', $date->toDateString()))
            ->pluck('completed_on');
        $trainerDates = $this->trainerSessionQuery($user, $startsAt, $endsAt, $recordedAfter)
            ->pluck('confirmed_start_at');

        return $this->gamification->streaksFrom($workoutDates->merge($wellnessDates)->merge($trainerDates));
    }

    private function trainerSessionQuery(User $user, ?Carbon $startsAt, ?Carbon $endsAt, ?Carbon $recordedAfter): HasMany
    {
        return $user->trainerBookings()
            ->where('status', TrainerBooking::STATUS_COMPLETED)
            ->whereNotNull('confirmed_start_at')
            ->where('confirmed_start_at', '<=', now())
            ->when($recordedAfter, fn ($query, Carbon $date) => $query->where('updated_at', '>=', $date))
            ->when($startsAt, fn ($query, Carbon $date) => $query->where('confirmed_start_at', '>=', $date))
            ->when($endsAt, fn ($query, Carbon $date) => $query->where('confirmed_start_at', '<=', $date));
    }
}
