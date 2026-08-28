<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\GamificationMission;
use Illuminate\Database\Seeder;

class GamificationSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'kind' => GamificationMission::KIND_QUEST,
                'title' => 'Workout Starter',
                'slug' => 'workout-starter',
                'description' => 'Join the quest, then complete three saved workout plans on any three eligible completions.',
                'metric' => GamificationMission::METRIC_WORKOUTS,
                'target_value' => 3,
                'reward_xp' => 30,
                'starts_on' => null,
                'ends_on' => null,
            ],
            [
                'kind' => GamificationMission::KIND_QUEST,
                'title' => 'Mindful Momentum',
                'slug' => 'mindful-momentum',
                'description' => 'Complete five saved Mind activities after joining this open-ended quest.',
                'metric' => GamificationMission::METRIC_WELLNESS,
                'target_value' => 5,
                'reward_xp' => 35,
                'starts_on' => null,
                'ends_on' => null,
            ],
            [
                'kind' => GamificationMission::KIND_CHALLENGE,
                'title' => '90-Day Consistency Challenge',
                'slug' => '90-day-consistency-challenge',
                'description' => 'Record qualifying activity on twelve distinct days during the published challenge window.',
                'metric' => GamificationMission::METRIC_ACTIVE_DAYS,
                'target_value' => 12,
                'reward_xp' => 60,
                'starts_on' => '2026-08-01',
                'ends_on' => '2026-10-31',
            ],
        ] as $mission) {
            GamificationMission::updateOrCreate(
                ['slug' => $mission['slug']],
                $mission + ['status' => GamificationMission::STATUS_PUBLISHED],
            );
        }

        foreach ([
            ['title' => 'First Rep', 'slug' => 'first-rep', 'description' => 'Complete your first recorded workout.', 'metric' => GamificationMission::METRIC_WORKOUTS, 'threshold' => 1, 'sort_order' => 10],
            ['title' => 'Mindful Five', 'slug' => 'mindful-five', 'description' => 'Complete five recorded Mind activities.', 'metric' => GamificationMission::METRIC_WELLNESS, 'threshold' => 5, 'sort_order' => 20],
            ['title' => 'Week Builder', 'slug' => 'week-builder', 'description' => 'Build a seven-day activity streak.', 'metric' => GamificationMission::METRIC_LONGEST_STREAK, 'threshold' => 7, 'sort_order' => 30],
            ['title' => 'Session Regular', 'slug' => 'session-regular', 'description' => 'Complete three trainer sessions.', 'metric' => GamificationMission::METRIC_TRAINER_SESSIONS, 'threshold' => 3, 'sort_order' => 40],
            ['title' => 'XP Century', 'slug' => 'xp-century', 'description' => 'Reach one hundred total XP from documented rules.', 'metric' => Achievement::METRIC_TOTAL_XP, 'threshold' => 100, 'sort_order' => 50],
        ] as $achievement) {
            Achievement::updateOrCreate(
                ['slug' => $achievement['slug']],
                $achievement + ['is_active' => true],
            );
        }
    }
}
