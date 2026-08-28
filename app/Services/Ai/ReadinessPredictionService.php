<?php

namespace App\Services\Ai;

use App\Models\MonthlyProgressReview;
use App\Models\ProgressionReadinessPrediction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReadinessPredictionService
{
    public function __construct(
        private ReadinessFeatureService $features,
        private ReadinessInferenceClient $inference,
    ) {}

    public function candidates(int $limit = 20): Collection
    {
        return MonthlyProgressReview::query()
            ->whereNotNull('ready_for_progression')
            ->whereNotNull('readiness_assessed_at')
            ->with(['member.latestProgressionReadinessPrediction', 'trainerProfile.user'])
            ->latest('readiness_assessed_at')
            ->get()
            ->filter(fn (MonthlyProgressReview $review): bool => $review->member?->hasRole('member') ?? false)
            ->unique('user_id')
            ->take(max(1, $limit))
            ->values();
    }

    public function predictFor(User $member): ReadinessPredictionOutcome
    {
        if (! $member->hasRole('member')) {
            return ReadinessPredictionOutcome::unavailable(
                'not_a_member',
                'Progression readiness can only be evaluated for a member account.',
            );
        }

        $review = $member->monthlyProgressReviews()
            ->whereNotNull('ready_for_progression')
            ->whereNotNull('readiness_assessed_at')
            ->latest('readiness_assessed_at')
            ->first();

        if (! $review) {
            return ReadinessPredictionOutcome::unavailable(
                'missing_trainer_assessment',
                'A genuine trainer readiness assessment is required before requesting a model evaluation.',
            );
        }

        $featureSnapshot = $this->features->currentFor($member, $review->trainer_profile_id);
        $result = $this->inference->predict($featureSnapshot);

        if (! $result->available) {
            return ReadinessPredictionOutcome::unavailable(
                $result->errorCode ?? 'model_unavailable',
                $result->errorMessage ?? 'No reviewed local model result is available.',
            );
        }

        $observationMonth = today()->startOfMonth();
        $fingerprint = hash('sha256', json_encode([
            'contract_version' => 1,
            'observation_month' => $observationMonth->toDateString(),
            'monthly_progress_review_id' => $review->id,
            'features' => $featureSnapshot,
        ], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
        $identity = [
            'user_id' => $member->id,
            'model_version' => $result->modelVersion,
            'input_fingerprint' => $fingerprint,
        ];
        $values = [
            'observation_month' => $observationMonth,
            'monthly_progress_review_id' => $review->id,
            'predicted_ready' => $result->predictedReady,
            'readiness_probability' => $result->probability,
            'feature_snapshot' => $featureSnapshot,
            'explanation' => [
                'method' => 'global_permutation_importance',
                'factors' => $result->explanation,
                'decision_threshold' => $result->decisionThreshold,
                'disclaimer' => $result->disclaimer,
            ],
            'predicted_at' => now(),
        ];

        try {
            $prediction = DB::transaction(function () use ($identity, $values): ProgressionReadinessPrediction {
                $existing = ProgressionReadinessPrediction::query()
                    ->where($identity)
                    ->lockForUpdate()
                    ->first();

                return $existing ?? ProgressionReadinessPrediction::create($identity + $values);
            });
        } catch (QueryException $exception) {
            $prediction = ProgressionReadinessPrediction::query()->where($identity)->first();

            if (! $prediction) {
                throw $exception;
            }
        }

        $created = $prediction->wasRecentlyCreated;

        return new ReadinessPredictionOutcome(
            succeeded: true,
            created: $created,
            prediction: $prediction,
            message: $created
                ? 'A new advisory readiness prediction was recorded for human review.'
                : 'The identical reviewed model result was already recorded; no duplicate was created.',
        );
    }
}
