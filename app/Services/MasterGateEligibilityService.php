<?php

namespace App\Services;

use App\Models\GamificationMission;
use App\Models\MasterGateApplication;
use App\Models\MonthlyProgressReview;
use App\Models\ProgressionReadinessPrediction;
use App\Models\User;

class MasterGateEligibilityService
{
    public function __construct(
        private GamificationProgressService $progress,
        private GamificationService $gamification,
    ) {}

    public function summaryFor(User $member): array
    {
        $this->progress->syncFor($member);
        $gamification = $this->gamification->summaryFor($member);
        $completedChallenges = $member->memberMissions()
            ->whereNotNull('completed_at')
            ->whereHas('mission', fn ($query) => $query->where('kind', GamificationMission::KIND_CHALLENGE))
            ->count();
        $latestTrainerAssessment = $member->monthlyProgressReviews()
            ->whereNotNull('ready_for_progression')
            ->whereNotNull('readiness_assessed_at')
            ->with('trainerProfile.user')
            ->latest('readiness_assessed_at')
            ->first();
        $latestPrediction = $member->progressionReadinessPredictions()
            ->latest('predicted_at')
            ->first();

        $trainerAssessmentValidDays = max(1, (int) config('master_gate.trainer_assessment_valid_days', 120));
        $predictionValidDays = max(1, (int) config('master_gate.prediction_valid_days', 90));
        $trainerAssessmentIsCurrent = $latestTrainerAssessment?->readiness_assessed_at
            ?->gte(now()->subDays($trainerAssessmentValidDays)) ?? false;
        $predictionIsCurrent = $latestPrediction?->predicted_at
            ?->gte(now()->subDays($predictionValidDays)) ?? false;

        $applicationCriteria = [
            $this->criterion(
                'level',
                'Automatic level',
                $gamification['level'] >= (int) config('master_gate.minimum_level', 6),
                'Level '.$gamification['level'],
                'Level '.(int) config('master_gate.minimum_level', 6).' or higher',
                'Level comes only from the published XP rules.',
            ),
            $this->criterion(
                'completed_challenges',
                'Completed timed challenges',
                $completedChallenges >= (int) config('master_gate.minimum_completed_challenges', 1),
                (string) $completedChallenges,
                (int) config('master_gate.minimum_completed_challenges', 1).' or more',
                'Only joined challenges with a stored completion count.',
            ),
            $this->criterion(
                'active_days',
                'Distinct active days',
                $gamification['active_day_count'] >= (int) config('master_gate.minimum_active_days', 30),
                (string) $gamification['active_day_count'],
                (int) config('master_gate.minimum_active_days', 30).' or more',
                'Workout, Mind-activity and completed trainer-session dates are merged once per day.',
            ),
            $this->criterion(
                'longest_streak',
                'Longest activity streak',
                $gamification['longest_streak'] >= (int) config('master_gate.minimum_longest_streak', 7),
                $gamification['longest_streak'].' days',
                (int) config('master_gate.minimum_longest_streak', 7).' days or longer',
                'A historical consecutive-day streak; it does not need to be the current streak.',
            ),
            $this->criterion(
                'trainer_assessment',
                'Recent trainer assessment',
                $latestTrainerAssessment?->ready_for_progression === true && $trainerAssessmentIsCurrent,
                $this->trainerAssessmentValue($latestTrainerAssessment, $trainerAssessmentIsCurrent),
                'Ready assessment within '.$trainerAssessmentValidDays.' days',
                'This is the trainer’s recorded professional judgment, not an AI result.',
            ),
        ];

        $aiCriterion = $this->criterion(
            'ai_readiness',
            'Local AI readiness result',
            $latestPrediction?->predicted_ready === true && $predictionIsCurrent,
            $this->predictionValue($latestPrediction, $predictionIsCurrent),
            'Ready result within '.$predictionValidDays.' days',
            $latestPrediction
                ? 'Result from model '.$latestPrediction->model_version.'. It supports but never makes the final decision.'
                : 'No prediction exists because a genuine model has not yet been exported and integrated.',
        );
        $criteria = [...$applicationCriteria, $aiCriterion];
        $pendingApplication = $member->masterGateApplications()
            ->where('status', MasterGateApplication::STATUS_PENDING)
            ->latest('requested_at')
            ->first();
        $approvedApplication = $member->masterGateApplications()
            ->where('status', MasterGateApplication::STATUS_APPROVED)
            ->latest('decided_at')
            ->first();

        return [
            'gamification' => $gamification,
            'criteria' => $criteria,
            'application_criteria' => $applicationCriteria,
            'ai_criterion' => $aiCriterion,
            'application_requirements_met' => collect($applicationCriteria)->every(fn (array $criterion): bool => $criterion['met']),
            'full_requirements_met' => collect($criteria)->every(fn (array $criterion): bool => $criterion['met']),
            'completed_challenge_count' => $completedChallenges,
            'latest_trainer_assessment' => $latestTrainerAssessment,
            'latest_prediction' => $latestPrediction,
            'pending_application' => $pendingApplication,
            'approved_application' => $approvedApplication,
            'access_granted' => $approvedApplication !== null,
        ];
    }

    public function snapshot(array $summary): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'criteria' => collect($summary['criteria'])->map(fn (array $criterion): array => [
                'key' => $criterion['key'],
                'label' => $criterion['label'],
                'met' => $criterion['met'],
                'current' => $criterion['current'],
                'required' => $criterion['required'],
            ])->values()->all(),
            'total_xp' => $summary['gamification']['total_xp'],
            'level' => $summary['gamification']['level'],
            'rank' => $summary['gamification']['current_rank']['name'],
            'prediction_id' => $summary['latest_prediction']?->id,
            'prediction_model_version' => $summary['latest_prediction']?->model_version,
        ];
    }

    private function criterion(
        string $key,
        string $label,
        bool $met,
        string $current,
        string $required,
        string $explanation,
    ): array {
        return compact('key', 'label', 'met', 'current', 'required', 'explanation');
    }

    private function trainerAssessmentValue(
        ?MonthlyProgressReview $assessment,
        bool $isCurrent,
    ): string {
        if (! $assessment) {
            return 'Not assessed';
        }

        $label = $assessment->ready_for_progression ? 'Ready' : 'Not ready yet';
        $freshness = $isCurrent ? 'current' : 'expired';

        return $label.' · '.$assessment->readiness_assessed_at->format('d M Y').' · '.$freshness;
    }

    private function predictionValue(
        ?ProgressionReadinessPrediction $prediction,
        bool $isCurrent,
    ): string {
        if (! $prediction) {
            return 'Not evaluated';
        }

        $label = $prediction->predicted_ready ? 'Ready' : 'Not ready';
        $probability = $prediction->readiness_probability !== null
            ? ' · '.number_format((float) $prediction->readiness_probability * 100, 1).'%'
            : '';
        $freshness = $isCurrent ? 'current' : 'expired';

        return $label.$probability.' · '.$prediction->predicted_at->format('d M Y').' · '.$freshness;
    }
}
