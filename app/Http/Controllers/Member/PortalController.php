<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\GamificationProgressService;
use App\Services\GamificationService;
use App\Services\GameLevelProgressionService;
use App\Services\MemberDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function progression(
        Request $request,
        GamificationService $gamification,
        GamificationProgressService $progress,
        GameLevelProgressionService $gameLevels,
    ): View {
        $progress->syncFor($request->user());

        return view('member.progression.index', [
            'gamification' => $gamification->summaryFor($request->user()),
            'gameProgression' => $gameLevels->summaryFor($request->user()),
        ]);
    }

    public function progress(Request $request, MemberDashboardService $dashboard): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $month = isset($validated['month'])
            ? Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth()
            : today()->startOfMonth();

        abort_if(
            $month->isAfter(today()->startOfMonth()),
            422,
            'Future monthly progress is not available.',
        );

        return view('member.progress.index', $dashboard->dataFor($request->user(), $month));
    }

    public function library(Request $request, MemberDashboardService $dashboard): View
    {
        return view('member.library.index', $dashboard->dataFor($request->user()));
    }

    public function mealPlan(Request $request, MemberDashboardService $dashboard): View
    {
        return view('member.meal-plan.index', $dashboard->dataFor($request->user()));
    }

    public function schedules(Request $request, MemberDashboardService $dashboard): View
    {
        $data = $dashboard->dataFor($request->user());
        $data['groupProgramRegistrations'] = $request->user()
            ->groupProgramRegistrations()
            ->with('groupProgram.trainerProfile.user')
            ->whereIn('status', ['pending', 'confirmed'])
            ->latest()
            ->limit(10)
            ->get();

        return view('member.schedules.index', $data);
    }
}
