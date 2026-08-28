<?php

namespace App\Services\Ai;

use App\Models\MonthlyProgressReview;
use App\Models\ReadinessLabelRevision;
use Illuminate\Support\Str;

class LabelQualityService
{
    public const MINIMUM_RATIONALE_CHARACTERS = 20;

    public const FREQUENT_REVISION_THRESHOLD = 3;

    public function report(): array
    {
        $labels = MonthlyProgressReview::query()
            ->whereNotNull('ready_for_progression')
            ->whereNotNull('readiness_assessed_at')
            ->with(['member:id,name', 'trainerProfile.user:id,name'])
            ->get();
        $conflicts = $labels
            ->groupBy(fn (MonthlyProgressReview $review): string => $review->user_id.'|'.$review->review_month->format('Y-m'))
            ->filter(fn ($group): bool => $group->pluck('ready_for_progression')->unique(strict: true)->count() > 1)
            ->map(function ($group): array {
                /** @var MonthlyProgressReview $first */
                $first = $group->first();

                return [
                    'user_id' => $first->user_id,
                    'member_name' => $first->member?->name ?? 'Deleted member',
                    'observation_month' => $first->review_month,
                    'decisions' => $group->sortBy('readiness_assessed_at')->map(fn (MonthlyProgressReview $review): array => [
                        'trainer_name' => $review->trainerProfile?->user?->name ?? 'Deleted trainer',
                        'label' => $review->ready_for_progression,
                        'assessed_at' => $review->readiness_assessed_at,
                    ])->values(),
                ];
            })
            ->values();
        $shortRationaleCount = $labels->filter(
            fn (MonthlyProgressReview $review): bool => Str::length(trim((string) $review->readiness_rationale)) < self::MINIMUM_RATIONALE_CHARACTERS,
        )->count();
        $frequentlyRevisedCount = ReadinessLabelRevision::query()
            ->selectRaw('monthly_progress_review_id, COUNT(*) AS revision_count')
            ->groupBy('monthly_progress_review_id')
            ->havingRaw('COUNT(*) >= ?', [self::FREQUENT_REVISION_THRESHOLD])
            ->get()
            ->count();
        $trainerCounts = $labels->countBy('trainer_profile_id')->sortDesc();
        $classCounts = $labels->countBy(fn (MonthlyProgressReview $review): string => $review->ready_for_progression ? 'ready' : 'not_ready');
        $dominantTrainerShare = $labels->isEmpty()
            ? null
            : round(((int) $trainerCounts->first() / $labels->count()) * 100, 1);
        $minorityClassShare = $labels->isEmpty() || $classCounts->count() < 2
            ? null
            : round(((int) $classCounts->min() / $labels->count()) * 100, 1);

        return [
            'conflict_count' => $conflicts->count(),
            'short_rationale_count' => $shortRationaleCount,
            'frequently_revised_count' => $frequentlyRevisedCount,
            'dominant_trainer_share' => $dominantTrainerShare,
            'minority_class_share' => $minorityClassShare,
            'has_blocking_issues' => $conflicts->isNotEmpty(),
            'conflicts' => $conflicts,
        ];
    }
}
