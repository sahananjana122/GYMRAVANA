<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\GamificationMission;
use App\Models\MemberMission;
use App\Services\GamificationProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MissionController extends Controller
{
    public function index(Request $request, GamificationProgressService $progress): View
    {
        return view('member.missions.index', $progress->overviewFor($request->user()));
    }

    public function join(
        Request $request,
        GamificationMission $gamificationMission,
        GamificationProgressService $progress,
    ): RedirectResponse {
        if (! $gamificationMission->isJoinable()) {
            return back()->withErrors([
                'mission' => 'This mission is not currently open for joining.',
            ]);
        }

        $participation = MemberMission::firstOrCreate(
            [
                'gamification_mission_id' => $gamificationMission->id,
                'user_id' => $request->user()->id,
            ],
            ['joined_at' => now()],
        );

        $progress->syncFor($request->user());

        $message = $participation->wasRecentlyCreated
            ? 'Mission joined. Activity saved from this point forward will update its progress.'
            : 'You already joined this mission.';

        return redirect()->route('member.missions.index')->with('status', $message);
    }
}
