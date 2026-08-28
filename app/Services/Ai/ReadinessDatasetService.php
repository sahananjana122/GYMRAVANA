<?php

namespace App\Services\Ai;

use App\Models\MonthlyProgressReview;
use Generator;
use RuntimeException;

class ReadinessDatasetService
{
    public const SCHEMA_VERSION = 1;

    public const TARGET = 'ready_for_progression';

    public const HEADERS = [
        'member_key',
        'observation_month',
        'label_recorded_at',
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
        'previous_assessment',
        'workout_change',
        'consistency_change',
        self::TARGET,
    ];

    public function __construct(private ReadinessFeatureService $features) {}

    public function rows(): Generator
    {
        $reviews = MonthlyProgressReview::query()
            ->whereNotNull('ready_for_progression')
            ->whereNotNull('readiness_assessed_at')
            ->with('member')
            ->orderBy('id')
            ->lazyById(100);

        foreach ($reviews as $review) {
            if (! $review->member) {
                continue;
            }

            yield $this->row($review);
        }
    }

    private function row(MonthlyProgressReview $review): array
    {
        $monthStart = $review->review_month->copy()->startOfMonth();

        return [
            'member_key' => $this->memberKey($review->user_id),
            'observation_month' => $monthStart->format('Y-m'),
            'label_recorded_at' => $review->readiness_assessed_at->toIso8601String(),
            ...$this->features->forReview($review),
            'ready_for_progression' => $review->ready_for_progression ? 1 : 0,
        ];
    }

    private function memberKey(int $userId): string
    {
        $key = (string) config('app.key');

        if ($key === '') {
            throw new RuntimeException('APP_KEY is required to pseudonymize readiness data.');
        }

        return substr(hash_hmac('sha256', 'member:'.$userId, $key), 0, 20);
    }
}
