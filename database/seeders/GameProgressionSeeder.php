<?php

namespace Database\Seeders;

use App\Models\GameGoal;
use App\Models\GameLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GameProgressionSeeder extends Seeder
{
    public function run(): void
    {
        if (GameLevel::query()->exists()) {
            return;
        }

        DB::transaction(function (): void {
            foreach ($this->levels() as $levelData) {
                $goals = $levelData['goals'];
                unset($levelData['goals']);

                $level = GameLevel::create($levelData);

                foreach ($goals as $order => $goal) {
                    $level->goals()->create($goal + [
                        'sort_order' => ($order + 1) * 10,
                        'is_active' => true,
                    ]);
                }
            }
        });
    }

    private function levels(): array
    {
        return [
            $this->level(1, false, [
                $this->goal('level-1-veerasana-duration', 'Veerasana', GameGoal::METRIC_DURATION, 30),
                $this->goal('level-1-veerasana-stability', 'Veerasana', GameGoal::METRIC_STABILITY, 1, 'No unacceptable spine instability or shaking.'),
                $this->goal('level-1-chakrasana-form', 'Chakrasana', GameGoal::METRIC_PERCENTAGE, 50),
            ]),
            $this->level(2, false, [
                $this->goal('level-2-veerasana-duration', 'Veerasana', GameGoal::METRIC_DURATION, 60),
                $this->goal('level-2-chakrasana-form', 'Chakrasana', GameGoal::METRIC_PERCENTAGE, 80),
                $this->goal('level-2-samathulana-duration', 'Samathulana Asana', GameGoal::METRIC_DURATION, 5),
            ]),
            $this->level(3, false, [
                $this->goal('level-3-veerasana-duration', 'Veerasana', GameGoal::METRIC_DURATION, 60),
                $this->goal('level-3-samathulana-duration', 'Samathulana Asana', GameGoal::METRIC_DURATION, 10),
                $this->goal('level-3-adhomukha-form', 'Adhomukha Veerasana', GameGoal::METRIC_PERCENTAGE, 50),
                $this->goal('level-3-chakrasana-form', 'Chakrasana', GameGoal::METRIC_PERCENTAGE, 100),
            ]),
            $this->level(4, false, [
                $this->goal('level-4-veerasana-duration', 'Veerasana', GameGoal::METRIC_DURATION, 60),
                $this->goal('level-4-samathulana-duration', 'Samathulana Asana', GameGoal::METRIC_DURATION, 15),
                $this->goal('level-4-adhomukha-form', 'Adhomukha Veerasana', GameGoal::METRIC_PERCENTAGE, 60),
                $this->goal('level-4-chakrasana-form', 'Chakrasana', GameGoal::METRIC_PERCENTAGE, 100),
                $this->goal('level-4-mayura-form', 'Mayura Asana', GameGoal::METRIC_PERCENTAGE, 80),
            ]),
            $this->level(5, false, [
                $this->goal('level-5-veerasana-duration', 'Veerasana', GameGoal::METRIC_DURATION, 60),
                $this->goal('level-5-samathulana-duration', 'Samathulana Asana', GameGoal::METRIC_DURATION, 15),
                $this->goal('level-5-adhomukha-form', 'Adhomukha Veerasana', GameGoal::METRIC_PERCENTAGE, 65),
                $this->goal('level-5-chakrasana-form', 'Chakrasana', GameGoal::METRIC_PERCENTAGE, 100),
                $this->goal('level-5-mayura-form', 'Mayura Asana', GameGoal::METRIC_PERCENTAGE, 85),
            ]),
            $this->level(6, true, [
                $this->goal('level-6-veerasana-duration', 'Veerasana', GameGoal::METRIC_DURATION, 60),
                $this->goal('level-6-samathulana-duration', 'Samathulana Asana', GameGoal::METRIC_DURATION, 15),
                $this->goal('level-6-adhomukha-form', 'Adhomukha Veerasana', GameGoal::METRIC_PERCENTAGE, 70),
                $this->goal('level-6-chakrasana-form', 'Chakrasana', GameGoal::METRIC_PERCENTAGE, 100),
                $this->goal('level-6-mayura-form', 'Mayura Asana', GameGoal::METRIC_PERCENTAGE, 90),
                $this->goal('level-6-running', 'Continuous Running', GameGoal::METRIC_PACE_DURATION, 30, 'Complete continuously at the administrator-configured pace.', GameGoal::VALIDATION_ACTIVITY),
                $this->goal('level-6-push-ups', 'Push-ups', GameGoal::METRIC_REPETITIONS, 20),
                $this->goal('level-6-dips', 'Dips', GameGoal::METRIC_REPETITIONS, 20),
            ]),
        ];
    }

    private function level(int $number, bool $unlocksMasterGate, array $goals): array
    {
        return [
            'number' => $number,
            'name' => 'Level '.$number,
            'description' => $unlocksMasterGate
                ? 'Complete every requirement to unlock the Master Gate review path.'
                : 'Complete every requirement to unlock Level '.($number + 1).'.',
            'is_active' => true,
            'unlocks_master_gate' => $unlocksMasterGate,
            'goals' => $goals,
        ];
    }

    private function goal(
        string $slug,
        string $exercise,
        string $metric,
        float $target,
        ?string $instructions = null,
        string $validation = GameGoal::VALIDATION_AI_TRAINER,
    ): array {
        return [
            'slug' => $slug,
            'exercise_name' => $exercise,
            'metric_type' => $metric,
            'target_value' => $target,
            'pace_target' => null,
            'pace_unit' => null,
            'validation_method' => $validation,
            'instructions' => $instructions,
        ];
    }
}
