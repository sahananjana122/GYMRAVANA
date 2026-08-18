<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\TrainerBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->trainerProfile;

        return view('trainer.bookings', ['bookings' => $profile?->bookings()->with('member')->latest()->get() ?? collect()]);
    }

    public function update(Request $request, TrainerBooking $trainerBooking): RedirectResponse
    {
        abort_unless($trainerBooking->trainerProfile?->user_id === $request->user()->id, 403);
        $trainerBooking->update($request->validate(['status' => ['required', Rule::in(['accepted', 'declined', 'completed', 'cancelled'])]]));

        return back()->with('status', 'Booking request updated.');
    }
}
