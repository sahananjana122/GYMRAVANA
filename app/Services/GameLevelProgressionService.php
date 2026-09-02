<?php

namespace App\Services;

use App\Models\GameGoal;
use App\Models\GameLevel;
use App\Models\MemberGameGoalProgress;
use App\Models\User;
use InvalidArgumentException;

class GameLevelProgressionService
{
    public const SOURCES = ['ai', 'trainer', 'activity', 'admin'];

    public function summaryFor(User $member): array
    {
        $progress = $member->gameGoalProgress()->get()->keyBy('game_goal_id');
        $previousCompleted = true;

        $levels = GameLevel::query()
            ->active()
            ->with(['goals' => fn ($query) => $query->active()])
            ->orderBy('number')
            ->get()
            ->map(function (GameLevel $level) use ($progress, &$previousCompleted): array {
                $goals = $level->goals->map(function (GameGoal $goal) use ($progress): array {
                    $record = $progress->get($goal->id);
                    $currentValue = (float) ($record?->current_value ?? 0);
                    $paceValue = $record?->pace_value !== null ? (float) $record->pace_value : null;
                    $achieved = $this->goalIsMet($goal, $currentValue, $paceValue);

                    return [
                        'goal' => $goal,
                        'progress' => $record,
                        'current_value' => $currentValue,
                        'pace_value' => $paceValue,
                        'achieved' => $achieved,
                        'percent' => $this->goalPercent($goal, $currentValue, $paceValue),
                        'current_label' => $goal->progressLabel($currentValue, $paceValue),
                        'required_label' => $goal->requirementLabel(),
                    ];
                });

                $completed = $goals->isNotEmpty() && $goals->every(fn (array $goal): bool => $goal['achieved']);
                $unlocked = $previousCompleted;
                $previousCompleted = $previousCompleted && $completed;

                return [
                    'level' => $level,
                    'goals' => $goals,
                    'unlocked' => $unlocked,
                    'completed' => $completed,
                    'percent' => $goals->isEmpty()
                        ? 0
                        : (int) floor($goals->sum('percent') / $goals->count()),
                ];
            });

        $current = $levels->first(fn (array $level): bool => $level['unlocked'] && ! $level['completed'])
            ?? $levels->last();
        $masterLevel = $levels->last(fn (array $level): bool => $level['level']->unlocks_master_gate);
        $levelsThroughMaster = $masterLevel
            ? $levels->takeUntil(fn (array $level): bool => $level['level']->id === $masterLevel['level']->id)->push($masterLevel)
            : collect();
        $masterGateUnlocked = $masterLevel !== null
            && $levelsThroughMaster->isNotEmpty()
            && $levelsThroughMaster->every(fn (array $level): bool => $level['completed']);

        return [
            'levels' => $levels,
            'current' => $current,
            'highest_completed_level' => $levels->where('completed', true)->max(fn (array $level) => $level['level']->number) ?? 0,
            'master_level' => $masterLevel,
            'master_gate_unlocked' => $masterGateUnlocked,
        ];
    }

    public function record(
        User $member,
        GameGoal $goal,
        float $value,
        ?float $paceValue,
        string $source,
        ?User $recordedBy = null,
        ?array $evidence = null,
    ): MemberGameGoalProgress {
        if (! $member->hasRole('member')) {
            throw new InvalidArgumentException('Game progress can only be recorded for a member.');
        }

        if (! in_array($source, self::SOURCES, true)) {
            throw new InvalidArgumentException('Unknown game progress source.');
        }

        if ($value < 0 || ($paceValue !== null && $paceValue < 0)) {
            throw new InvalidArgumentException('Game progress values cannot be negative.');
        }

        $record = MemberGameGoalProgress::firstOrNew([
            'user_id' => $member->id,
            'game_goal_id' => $goal->id,
        ]);
        $bestValue = max((float) ($record->current_value ?? 0), $value);
        $bestPace = $this->bestPace($goal, $record->pace_value, $paceValue);
        $achieved = $this->goalIsMet($goal, $bestValue, $bestPace);

        $record->fill([
            'current_value' => $bestValue,
            'pace_value' => $bestPace,
            'source' => $source,
            'evidence' => $evidence,
            'recorded_by' => $recordedBy?->id,
            'recorded_at' => now(),
            'achieved_at' => $achieved ? ($record->achieved_at ?? now()) : null,
        ])->save();

        return $record->fresh();
    }

    private function goalIsMet(GameGoal $goal, float $value, ?float $pace): bool
    {
        if ($value < (float) $goal->target_value) {
            return false;
        }

        if ($goal->metric_type !== GameGoal::METRIC_PACE_DURATION) {
            return true;
        }

        if ($goal->pace_target === null || $pace === null) {
            return false;
        }

        return $goal->pace_unit === GameGoal::PACE_MIN_KM
            ? $pace <= (float) $goal->pace_target
            : $pace >= (float) $goal->pace_target;
    }

    private function goalPercent(GameGoal $goal, float $value, ?float $pace): int
    {
        if ($this->goalIsMet($goal, $value, $pace)) {
            return 100;
        }

        return min(99, (int) floor(($value / max(0.01, (float) $goal->target_value)) * 100));
    }

    private function bestPace(GameGoal $goal, mixed $stored, ?float $incoming): ?float
    {
        $stored = $stored !== null ? (float) $stored : null;

        if ($incoming === null) {
            return $stored;
        }

        if ($stored === null) {
            return $incoming;
        }

        return $goal->pace_unit === GameGoal::PACE_MIN_KM
            ? min($stored, $incoming)
            : max($stored, $incoming);
    }
}
