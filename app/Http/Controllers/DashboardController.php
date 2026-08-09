<?php

namespace App\Http\Controllers;

use App\Models\TherapyRequest;
use App\Models\User;
use App\Models\WellnessActivity;
use App\Models\WorkoutPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $route = $request->user()->dashboardRouteName();

        abort_if($route === 'dashboard', 403, 'Your account does not have an assigned role.');

        return redirect()->route($route);
    }

    public function member(Request $request): View
    {
        $user = $request->user();

        return view('dashboards.member', [
            'totalPoints' => $user->totalPoints(),
            'workoutCount' => $user->workoutCompletions()->count(),
            'wellnessCount' => $user->wellnessCompletions()->count(),
            'latestMeasurement' => $user->bodyMeasurements()->latest('recorded_on')->first(),
            'pendingTherapyRequests' => $user->therapyRequests()->where('status', 'pending')->count(),
            'availableWorkouts' => WorkoutPlan::where('is_active', true)->count(),
        ]);
    }

    public function admin(): View
    {
        return view('dashboards.admin', [
            'userCount' => User::count(),
            'memberCount' => User::role('member')->count(),
            'trainerCount' => User::role('trainer')->count(),
            'pendingTherapyRequests' => TherapyRequest::where('status', 'pending')->count(),
        ]);
    }

    public function trainer(): View
    {
        return view('dashboards.trainer', [
            'memberCount' => User::role('member')->count(),
            'pendingTherapyRequests' => TherapyRequest::where('status', 'pending')->count(),
            'activeWorkoutPlans' => WorkoutPlan::where('is_active', true)->count(),
        ]);
    }

    public function master(): View
    {
        $eligibleMembers = User::role('member')->get()
            ->filter(fn (User $user) => $user->totalPoints() >= 100)
            ->sortByDesc(fn (User $user) => $user->totalPoints());

        return view('dashboards.master', [
            'eligibleMembers' => $eligibleMembers,
            'wellnessActivityCount' => WellnessActivity::where('is_active', true)->count(),
        ]);
    }
}
