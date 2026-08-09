<?php

namespace App\Http\Controllers;

use App\Models\WellnessActivity;
use App\Models\WellnessCompletion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WellnessController extends Controller
{
    public function index(Request $request): View
    {
        return view('member.wellness.index', [
            'activities' => WellnessActivity::where('is_active', true)->orderBy('category')->get(),
            'completedToday' => $request->user()->wellnessCompletions()
                ->whereDate('completed_on', today())
                ->pluck('wellness_activity_id'),
        ]);
    }

    public function complete(Request $request, WellnessActivity $wellnessActivity): RedirectResponse
    {
        abort_unless($wellnessActivity->is_active, 404);

        $completion = WellnessCompletion::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'wellness_activity_id' => $wellnessActivity->id,
                'completed_on' => today()->toDateString(),
            ],
            ['points_awarded' => $wellnessActivity->points],
        );

        $message = $completion->wasRecentlyCreated
            ? "Activity completed. You earned {$wellnessActivity->points} points."
            : 'You already completed this activity today.';

        return back()->with('status', $message);
    }
}
