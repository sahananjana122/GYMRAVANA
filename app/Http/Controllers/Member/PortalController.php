<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\MemberDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function progress(Request $request, MemberDashboardService $dashboard): View
    {
        return view('member.progress.index', $dashboard->dataFor($request->user()));
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
