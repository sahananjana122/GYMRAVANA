<?php

namespace App\Services;

use App\Models\TrainerBooking;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GamificationService
{
    public function summaryFor(User $user): array
    {
        $workouts = $user->workoutCompletions()
            ->get(['completed_on', 'points_awarded']);
        $wellness = $user->wellnessCompletions()
            ->get(['completed_on', 'points_awarded']);
        $completedTrainerSessions = $user->trainerBookings()
            ->where('status', TrainerBooking::STATUS_COMPLETED)
            ->whereNotNull('confirmed_start_at')
            ->where('confirmed_start_at', '<=', now())
            ->get(['confirmed_start_at']);
        $completedGoalMonths = $user->monthlyProgressReviews()
            ->where('goal_completion_percent', '>=', 100)
            ->get(['review_month'])
            ->pluck('review_month')
            ->filter()
            ->map(fn ($month) => Carbon::parse($month)->format('Y-m'))
            ->unique()
            ->values();

        $activityDates = $workouts->pluck('completed_on')
            ->merge($wellness->pluck('completed_on'))
            ->merge($completedTrainerSessions->pluck('confirmed_start_at'));
        $streaks = $this->streaksFrom($activityDates);

        $workoutXp = (int) $workouts->sum('points_awarded');
        $wellnessXp = (int) $wellness->sum('points_awarded');
        $sessionXpRate = max(0, (int) config('gamification.completed_trainer_session_xp', 25));
        $goalXpRate = max(0, (int) config('gamification.completed_monthly_goal_xp', 30));
        $streakMilestoneDays = max(1, (int) config('gamification.streak_milestone_days', 7));
        $streakMilestoneXp = max(0, (int) config('gamification.streak_milestone_xp', 20));
        $sessionXp = $completedTrainerSessions->count() * $sessionXpRate;
        $goalXp = $completedGoalMonths->count() * $goalXpRate;
        $streakMilestones = intdiv($streaks['longest'], $streakMilestoneDays);
        $streakXp = $streakMilestones * $streakMilestoneXp;
        $completedMissions = $user->memberMissions()
            ->whereNotNull('completed_at')
            ->get(['reward_xp_awarded']);
        $missionXp = (int) $completedMissions->sum('reward_xp_awarded');
        $totalXp = $workoutXp + $wellnessXp + $sessionXp + $goalXp + $streakXp + $missionXp;

        $xpPerLevel = max(1, (int) config('gamification.xp_per_level', 100));
        $level = intdiv($totalXp, $xpPerLevel) + 1;
        $xpIntoLevel = $totalXp % $xpPerLevel;
        $rankData = $this->rankData($level, $xpPerLevel);

        return [
            'total_xp' => $totalXp,
            'level' => $level,
            'xp_per_level' => $xpPerLevel,
            'xp_into_level' => $xpIntoLevel,
            'xp_to_next_level' => $xpPerLevel - $xpIntoLevel,
            'next_level_total_xp' => $level * $xpPerLevel,
            'level_progress_percent' => (int) floor(($xpIntoLevel / $xpPerLevel) * 100),
            'current_rank' => $rankData['current'],
            'next_rank' => $rankData['next'],
            'rank_ladder' => $rankData['ladder'],
            'current_streak' => $streaks['current'],
            'longest_streak' => $streaks['longest'],
            'active_day_count' => $streaks['active_day_count'],
            'latest_activity_date' => $streaks['latest_date'],
            'sources' => [
                [
                    'key' => 'workouts',
                    'label' => 'Workout completions',
                    'count' => $workouts->count(),
                    'xp' => $workoutXp,
                    'rule' => '1 saved workout point = 1 XP',
                ],
                [
                    'key' => 'wellness',
                    'label' => 'Mind activity completions',
                    'count' => $wellness->count(),
                    'xp' => $wellnessXp,
                    'rule' => '1 saved wellness point = 1 XP',
                ],
                [
                    'key' => 'trainer_sessions',
                    'label' => 'Completed trainer sessions',
                    'count' => $completedTrainerSessions->count(),
                    'xp' => $sessionXp,
                    'rule' => $sessionXpRate.' XP per completed past session',
                ],
                [
                    'key' => 'monthly_goals',
                    'label' => 'Completed monthly goals',
                    'count' => $completedGoalMonths->count(),
                    'xp' => $goalXp,
                    'rule' => $goalXpRate.' XP per unique 100% month',
                ],
                [
                    'key' => 'streaks',
                    'label' => $streakMilestoneDays.'-day streak milestones',
                    'count' => $streakMilestones,
                    'xp' => $streakXp,
                    'rule' => $streakMilestoneXp.' XP per milestone in the longest streak',
                ],
                [
                    'key' => 'missions',
                    'label' => 'Completed quests and challenges',
                    'count' => $completedMissions->count(),
                    'xp' => $missionXp,
                    'rule' => 'The reward published when each mission was completed',
                ],
            ],
        ];
    }

    public function streaksFrom(Collection $dateValues): array
    {
        $dates = $dateValues
            ->filter()
            ->map(fn ($date) => Carbon::parse($date)->startOfDay())
            ->unique(fn (Carbon $date) => $date->toDateString())
            ->sortBy(fn (Carbon $date) => $date->timestamp)
            ->values();

        if ($dates->isEmpty()) {
            return [
                'current' => 0,
                'longest' => 0,
                'active_day_count' => 0,
                'latest_date' => null,
            ];
        }

        $longest = 1;
        $running = 1;
        $previous = $dates->first();

        foreach ($dates->slice(1) as $date) {
            $running = $previous->copy()->addDay()->isSameDay($date)
                ? $running + 1
                : 1;
            $longest = max($longest, $running);
            $previous = $date;
        }

        $latest = $dates->last();
        $current = ($latest->isToday() || $latest->isYesterday()) ? 1 : 0;

        if ($current === 1) {
            for ($index = $dates->count() - 2; $index >= 0; $index--) {
                $earlier = $dates[$index];
                $later = $dates[$index + 1];

                if (! $earlier->copy()->addDay()->isSameDay($later)) {
                    break;
                }

                $current++;
            }
        }

        return [
            'current' => $current,
            'longest' => $longest,
            'active_day_count' => $dates->count(),
            'latest_date' => $latest,
        ];
    }

    private function rankData(int $level, int $xpPerLevel): array
    {
        $ranks = collect(config('gamification.ranks', []))
            ->filter(fn ($rank) => is_array($rank) && isset($rank['name'], $rank['minimum_level']))
            ->map(fn ($rank) => [
                'name' => (string) $rank['name'],
                'minimum_level' => max(1, (int) $rank['minimum_level']),
                'minimum_xp' => (max(1, (int) $rank['minimum_level']) - 1) * $xpPerLevel,
                'description' => (string) ($rank['description'] ?? ''),
            ])
            ->sortBy('minimum_level')
            ->values();

        if ($ranks->isEmpty()) {
            $ranks = collect([[
                'name' => 'Member',
                'minimum_level' => 1,
                'minimum_xp' => 0,
                'description' => 'GymRAVANA member progression.',
            ]]);
        }

        $current = $ranks
            ->filter(fn ($rank) => $level >= $rank['minimum_level'])
            ->last() ?? $ranks->first();
        $next = $ranks->first(fn ($rank) => $level < $rank['minimum_level']);
        $ladder = $ranks->map(fn ($rank) => [
            ...$rank,
            'is_current' => $rank['name'] === $current['name'],
            'is_unlocked' => $level >= $rank['minimum_level'],
        ])->all();

        return compact('current', 'next', 'ladder');
    }
}
