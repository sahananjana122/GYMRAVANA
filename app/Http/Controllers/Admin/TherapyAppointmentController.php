<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TherapyAppointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TherapyAppointmentController extends Controller
{
    public function index(): View
    {
        return view('admin.therapy-appointments.index', [
            'appointments' => TherapyAppointment::with(['condition', 'treatment', 'specialist', 'user'])
                ->orderBy('preferred_datetime')
                ->get(),
        ]);
    }

    public function update(Request $request, TherapyAppointment $therapyAppointment): RedirectResponse
    {
        $therapyAppointment->update($request->validate([
            'status' => ['required', Rule::in(TherapyAppointment::STATUSES)],
        ]));

        return back()->with('status', 'Therapy appointment status updated.');
    }
}
