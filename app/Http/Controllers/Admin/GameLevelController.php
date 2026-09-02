<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GameGoalRequest;
use App\Http\Requests\Admin\GameLevelRequest;
use App\Models\GameGoal;
use App\Models\GameLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GameLevelController extends Controller
{
    public function index(Request $request): View
    {
        $levels = GameLevel::query()
            ->withCount(['goals', 'goals as active_goals_count' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('number')
            ->get();
        $selectedLevel = $request->integer('level')
            ? $levels->firstWhere('id', $request->integer('level'))
            : $levels->first();
        $selectedLevel?->load(['goals' => fn ($query) => $query->withCount('progressRecords')]);

        return view('admin.game-levels.index', [
            'levels' => $levels,
            'selectedLevel' => $selectedLevel,
            'metricLabels' => $this->metricLabels(),
            'validationLabels' => $this->validationLabels(),
            'paceUnitLabels' => $this->paceUnitLabels(),
        ]);
    }

    public function storeLevel(GameLevelRequest $request): RedirectResponse
    {
        $level = DB::transaction(function () use ($request): GameLevel {
            $data = $request->validated();

            if ($data['unlocks_master_gate']) {
                GameLevel::query()->update(['unlocks_master_gate' => false]);
            }

            return GameLevel::create($data);
        });

        return $this->toLevel($level, 'Level created. Add its first goal when you are ready.');
    }

    public function updateLevel(GameLevelRequest $request, GameLevel $gameLevel): RedirectResponse
    {
        DB::transaction(function () use ($request, $gameLevel): void {
            $data = $request->validated();

            if ($data['unlocks_master_gate']) {
                GameLevel::whereKeyNot($gameLevel->id)->update(['unlocks_master_gate' => false]);
            }

            $gameLevel->update($data);
        });

        return $this->toLevel($gameLevel, 'Level settings updated for every member.');
    }

    public function destroyLevel(GameLevel $gameLevel): RedirectResponse
    {
        if ($gameLevel->goals()->whereHas('progressRecords')->exists()) {
            return back()->withErrors([
                'level' => 'This level has member progress and cannot be deleted. Make it inactive instead.',
            ]);
        }

        $gameLevel->delete();

        return redirect()->route('admin.game-levels.index')->with('status', 'Unused level removed.');
    }

    public function storeGoal(GameGoalRequest $request, GameLevel $gameLevel): RedirectResponse
    {
        $data = $this->goalData($request);
        $gameLevel->goals()->create($data + [
            'slug' => $this->uniqueGoalSlug($gameLevel, $data['exercise_name']),
        ]);

        return $this->toLevel($gameLevel, 'Goal added. Member level requirements are now updated.');
    }

    public function updateGoal(GameGoalRequest $request, GameGoal $gameGoal): RedirectResponse
    {
        $gameGoal->update($this->goalData($request));

        return $this->toLevel($gameGoal->level, 'Goal requirement updated for every member.');
    }

    public function destroyGoal(GameGoal $gameGoal): RedirectResponse
    {
        if ($gameGoal->progressRecords()->exists()) {
            return back()->withErrors([
                'goal' => 'This goal has member progress and cannot be deleted. Make it inactive instead.',
            ]);
        }

        $level = $gameGoal->level;
        $gameGoal->delete();

        return $this->toLevel($level, 'Unused goal removed.');
    }

    private function goalData(GameGoalRequest $request): array
    {
        $data = $request->validated();

        if ($data['metric_type'] === GameGoal::METRIC_STABILITY) {
            $data['target_value'] = 1;
        }

        if ($data['metric_type'] !== GameGoal::METRIC_PACE_DURATION) {
            $data['pace_target'] = null;
            $data['pace_unit'] = null;
        }

        return $data;
    }

    private function uniqueGoalSlug(GameLevel $level, string $exercise): string
    {
        $base = 'level-'.$level->number.'-'.(Str::slug($exercise) ?: 'goal');
        $slug = $base;
        $suffix = 2;

        while (GameGoal::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function toLevel(GameLevel $level, string $status): RedirectResponse
    {
        return redirect()->route('admin.game-levels.index', ['level' => $level->id])
            ->with('status', $status);
    }

    private function metricLabels(): array
    {
        return [
            GameGoal::METRIC_DURATION => 'Time held (minutes)',
            GameGoal::METRIC_PERCENTAGE => 'Form completion (%)',
            GameGoal::METRIC_REPETITIONS => 'Valid repetitions',
            GameGoal::METRIC_STABILITY => 'Stability check',
            GameGoal::METRIC_PACE_DURATION => 'Continuous time + pace',
        ];
    }

    private function validationLabels(): array
    {
        return [
            GameGoal::VALIDATION_AI_TRAINER => 'AI evidence + trainer review',
            GameGoal::VALIDATION_TRAINER => 'Trainer review',
            GameGoal::VALIDATION_ACTIVITY => 'Saved activity record',
        ];
    }

    private function paceUnitLabels(): array
    {
        return [
            GameGoal::PACE_KMH => 'km/h (minimum speed)',
            GameGoal::PACE_MIN_KM => 'min/km (maximum time)',
        ];
    }
}
