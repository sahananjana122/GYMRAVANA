<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrainerBookingScheduleRequest;
use App\Models\TrainerBooking;
use App\Models\TrainerProfile;
use App\Services\SessionNotificationService;
use App\Services\TrainerBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingManagementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(TrainerBooking::STATUSES)],
            'trainer_profile_id' => ['nullable', 'integer', 'exists:trainer_profiles,id'],
            'date' => ['nullable', 'date'],
        ]);
        $bookings = TrainerBooking::query()
            ->with(['member', 'trainerProfile.user', 'scheduler'])
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['trainer_profile_id'] ?? null, fn ($query, int|string $profileId) => $query->where('trainer_profile_id', (int) $profileId))
            ->when($filters['date'] ?? null, function ($query, string $date): void {
                $query->where(function ($query) use ($date): void {
                    $query->whereDate('confirmed_start_at', $date)
                        ->orWhere(function ($query) use ($date): void {
                            $query->whereNull('confirmed_start_at')->whereDate('requested_datetime', $date);
                        });
                });
            })
            ->orderByRaw('COALESCE(confirmed_start_at, requested_datetime) DESC')
            ->paginate(20)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'filters' => $filters,
            'trainers' => TrainerProfile::approved()->with('user')->orderBy('slug')->get(),
        ]);
    }

    public function update(
        TrainerBookingScheduleRequest $request,
        TrainerBooking $trainerBooking,
        TrainerBookingService $bookings,
    ): RedirectResponse {
        $bookings->updateSchedule($trainerBooking, $request->validated(), $request->user());

        return back()->with('status', 'Booking schedule updated.');
    }

    public function remind(
        TrainerBooking $trainerBooking,
        SessionNotificationService $notifications,
    ): RedirectResponse {
        $notifications->remindTrainerBooking($trainerBooking);

        return back()->with('status', 'Session reminder recorded and sent through the available channels.');
    }
}
