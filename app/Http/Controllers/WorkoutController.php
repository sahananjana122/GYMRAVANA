<?php

namespace App\Http\Controllers;

use App\Models\WorkoutCompletion;
use App\Models\WorkoutPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkoutController extends Controller
{
    public function index(Request $request): View
    {
        return view('member.workouts.index', [
            'workouts' => WorkoutPlan::where('is_active', true)->orderBy('difficulty')->get(),
            'completedToday' => $request->user()->workoutCompletions()
                ->whereDate('completed_on', today())
                ->pluck('workout_plan_id'),
        ]);
    }

    public function complete(Request $request, WorkoutPlan $workoutPlan): RedirectResponse
    {
        abort_unless($workoutPlan->is_active, 404);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $completion = WorkoutCompletion::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'workout_plan_id' => $workoutPlan->id,
                'completed_on' => today()->toDateString(),
            ],
            [
                'notes' => $validated['notes'] ?? null,
                'points_awarded' => $workoutPlan->points,
            ],
        );

        $message = $completion->wasRecentlyCreated
            ? "Workout completed. You earned {$workoutPlan->points} points."
            : 'You already completed this workout today.';

        return back()->with('status', $message);
    }
}
