<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainerBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.bookings.index', ['bookings' => TrainerBooking::with(['member', 'trainerProfile.user'])->latest()->get()]);
    }

    public function update(Request $request, TrainerBooking $trainerBooking): RedirectResponse
    {
        $trainerBooking->update($request->validate(['status' => ['required', Rule::in(TrainerBooking::STATUSES)]]));

        return back()->with('status', 'Booking status updated.');
    }
}
