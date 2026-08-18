<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrainerApplicationController extends Controller
{
    public function index(): View
    {
        return view('admin.trainers.index', ['trainers' => TrainerProfile::with('user')->latest()->get()]);
    }

    public function update(Request $request, TrainerProfile $trainerProfile): RedirectResponse
    {
        $trainerProfile->update($request->validate(['status' => ['required', Rule::in(TrainerProfile::STATUSES)]]));

        return back()->with('status', 'Trainer application updated.');
    }
}
