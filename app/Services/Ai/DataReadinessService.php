<?php

namespace App\Services\Ai;

use App\Models\MonthlyProgressReview;
use App\Models\ReadinessLabelRevision;
use App\Models\TrainerBooking;
use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Support\Collection;

class DataReadinessService
{
    public function __construct(private readonly LabelQualityService $quality) {}

    public function summary(): array
    {
        $labels = MonthlyProgressReview::query()
            ->whereNotNull('ready_for_progression')
            ->whereNotNull('readiness_assessed_at')
            ->get([
                'id',
                'trainer_profile_id',
                'user_id',
                'review_month',
                'ready_for_progression',
                'readiness_assessed_at',
            ]);
        $ready = $labels->where('ready_for_progression', true);
        $notReady = $labels->where('ready_for_progression', false);
        $thresholds = [
            'minimum_rows' => (int) config('ai_readiness.minimum_rows'),
            'minimum_rows_per_class' => (int) config('ai_readiness.minimum_rows_per_class'),
            'minimum_member_groups' => (int) config('ai_readiness.minimum_member_groups'),
            'minimum_member_groups_per_class' => (int) config('ai_readiness.minimum_member_groups_per_class'),
        ];
        $counts = [
            'total_rows' => $labels->count(),
            'ready_rows' => $ready->count(),
            'not_ready_rows' => $notReady->count(),
            'member_groups' => $this->distinctCount($labels, 'user_id'),
            'ready_member_groups' => $this->distinctCount($ready, 'user_id'),
            'not_ready_member_groups' => $this->distinctCount($notReady, 'user_id'),
            'trainers' => $this->distinctCount($labels, 'trainer_profile_id'),
            'observation_months' => $labels->pluck('review_month')->filter()->map->format('Y-m')->unique()->count(),
        ];
        $checks = [
            $this->check('Labeled rows', $counts['total_rows'], $thresholds['minimum_rows']),
            $this->check('Ready rows', $counts['ready_rows'], $thresholds['minimum_rows_per_class']),
            $this->check('Not-ready rows', $counts['not_ready_rows'], $thresholds['minimum_rows_per_class']),
            $this->check('Distinct members', $counts['member_groups'], $thresholds['minimum_member_groups']),
            $this->check('Members represented in ready class', $counts['ready_member_groups'], $thresholds['minimum_member_groups_per_class']),
            $this->check('Members represented in not-ready class', $counts['not_ready_member_groups'], $thresholds['minimum_member_groups_per_class']),
        ];

        $quality = $this->quality->report();

        return [
            'counts' => $counts,
            'thresholds' => $thresholds,
            'checks' => $checks,
            'training_allowed' => collect($checks)->every('met') && ! $quality['has_blocking_issues'],
            'quality' => $quality,
            'first_assessed_at' => $labels->min('readiness_assessed_at'),
            'latest_assessed_at' => $labels->max('readiness_assessed_at'),
        ];
    }

    public function recentLabels(int $limit = 10): Collection
    {
        return MonthlyProgressReview::query()
            ->whereNotNull('ready_for_progression')
            ->whereNotNull('readiness_assessed_at')
            ->with(['member:id,name', 'trainerProfile.user:id,name'])
            ->latest('readiness_assessed_at')
            ->limit($limit)
            ->get();
    }

    public function collectionPipeline(?array $readiness = null): array
    {
        $readiness ??= $this->summary();
        $month = today()->startOfMonth();
        $relationships = TrainerBooking::query()
            ->whereIn('status', [
                TrainerBooking::STATUS_ACCEPTED,
                TrainerBooking::STATUS_COMPLETED,
            ])
            ->get(['trainer_profile_id', 'user_id'])
            ->unique(fn (TrainerBooking $booking): string => $this->relationshipKey(
                $booking->trainer_profile_id,
                $booking->user_id,
            ))
            ->values();
        $relationshipKeys = $relationships
            ->map(fn (TrainerBooking $booking): string => $this->relationshipKey(
                $booking->trainer_profile_id,
                $booking->user_id,
            ))
            ->all();
        $assessedRelationshipKeys = MonthlyProgressReview::query()
            ->whereDate('review_month', $month->toDateString())
            ->whereNotNull('ready_for_progression')
            ->whereNotNull('readiness_assessed_at')
            ->get(['trainer_profile_id', 'user_id'])
            ->map(fn (MonthlyProgressReview $review): string => $this->relationshipKey(
                $review->trainer_profile_id,
                $review->user_id,
            ))
            ->filter(fn (string $key): bool => in_array($key, $relationshipKeys, true))
            ->unique()
            ->values();

        $counts = [
            'member_accounts' => User::role('member')->count(),
            'approved_trainers' => TrainerProfile::approved()->count(),
            'pending_booking_requests' => TrainerBooking::query()
                ->where('status', TrainerBooking::STATUS_PENDING)
                ->count(),
            'valid_relationships' => $relationships->count(),
            'assigned_members' => $relationships->pluck('user_id')->unique()->count(),
            'current_month_assessed_relationships' => $assessedRelationshipKeys->count(),
            'current_month_needs_assessment' => max(
                0,
                $relationships->count() - $assessedRelationshipKeys->count(),
            ),
            'genuine_labels' => $readiness['counts']['total_rows'],
        ];
        $counts['members_without_relationship'] = max(
            0,
            $counts['member_accounts'] - $counts['assigned_members'],
        );

        return [
            'month' => $month,
            'counts' => $counts,
            'assessment_percent' => $counts['valid_relationships'] === 0
                ? 0
                : (int) round(
                    ($counts['current_month_assessed_relationships'] / $counts['valid_relationships']) * 100,
                ),
            'next_action' => $this->nextCollectionAction($counts, $readiness),
        ];
    }

    public function recentRevisions(int $limit = 10): Collection
    {
        return ReadinessLabelRevision::query()
            ->with([
                'member:id,name',
                'trainerProfile.user:id,name',
                'changedBy:id,name',
                'review:id,review_month',
            ])
            ->latest('changed_at')
            ->limit($limit)
            ->get();
    }

    private function distinctCount(Collection $labels, string $column): int
    {
        return $labels->pluck($column)->filter()->unique()->count();
    }

    private function relationshipKey(int $trainerProfileId, int $memberId): string
    {
        return $trainerProfileId.':'.$memberId;
    }

    private function nextCollectionAction(array $counts, array $readiness): array
    {
        if ($counts['valid_relationships'] === 0) {
            return [
                'stage' => 'relationships',
                'title' => 'Create genuine trainer-member relationships first',
                'description' => 'A member must request a real trainer session, then the trainer or administrator must accept and schedule it. Member accounts without an accepted or completed booking are not AI evidence.',
            ];
        }

        if ($counts['current_month_needs_assessment'] > 0) {
            return [
                'stage' => 'assessments',
                'title' => 'Complete this month’s trainer assessments',
                'description' => 'Assigned trainers should open Monthly Tracker and record an honest ready or not-ready decision with a behavioral rationale. Ordinary goals or notes do not count as a label.',
            ];
        }

        if (! $readiness['training_allowed']) {
            return [
                'stage' => 'continue_collection',
                'title' => 'Continue collecting balanced monthly evidence',
                'description' => 'This month’s assigned relationships are assessed, but the total, class-specific, member-group or quality gates are still blocked. Continue normal monthly collection without inventing outcomes.',
            ];
        }

        return [
            'stage' => 'notebook_review',
            'title' => 'Audit the export in Notebook 01',
            'description' => 'The minimum engineering checkpoint is met. Export the pseudonymized dataset and investigate its quality before attempting model training.',
        ];
    }

    private function check(string $label, int $current, int $required): array
    {
        return [
            'label' => $label,
            'current' => $current,
            'required' => $required,
            'remaining' => max(0, $required - $current),
            'met' => $current >= $required,
        ];
    }
}
