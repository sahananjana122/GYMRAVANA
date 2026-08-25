<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrainerBookingScheduleRequest;
use App\Models\TrainerBooking;
use App\Services\SessionNotificationService;
use App\Services\TrainerBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->trainerProfile;
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(TrainerBooking::STATUSES)],
            'date' => ['nullable', 'date'],
            'view' => ['nullable', Rule::in(['agenda', 'calendar'])],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        if (! $profile) {
            return view('trainer.bookings', [
                'profile' => null,
                'bookings' => collect(),
                'pendingRequests' => collect(),
                'todaySessions' => collect(),
                'upcomingCount' => 0,
                'completedCount' => 0,
                'cancelledCount' => 0,
                'calendarBookings' => collect(),
                'calendarMonth' => now()->startOfMonth(),
                'filters' => $filters,
            ]);
        }

        $calendarMonth = isset($filters['month'])
            ? Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth()
            : now()->startOfMonth();
        $baseQuery = $profile->bookings()->with(['member', 'scheduler']);
        $bookings = (clone $baseQuery)
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['date'] ?? null, function ($query, string $date): void {
                $query->where(function ($query) use ($date): void {
                    $query->whereDate('confirmed_start_at', $date)
                        ->orWhere(function ($query) use ($date): void {
                            $query->whereNull('confirmed_start_at')->whereDate('requested_datetime', $date);
                        });
                });
            })
            ->orderByRaw('COALESCE(confirmed_start_at, requested_datetime) ASC')
            ->paginate(15)
            ->withQueryString();

        return view('trainer.bookings', [
            'profile' => $profile,
            'bookings' => $bookings,
            'pendingRequests' => (clone $baseQuery)->where('status', TrainerBooking::STATUS_PENDING)->oldest('requested_datetime')->limit(8)->get(),
            'todaySessions' => (clone $baseQuery)->where('status', TrainerBooking::STATUS_ACCEPTED)->whereDate('confirmed_start_at', today())->orderBy('confirmed_start_at')->get(),
            'upcomingCount' => (clone $baseQuery)->upcoming()->count(),
            'completedCount' => (clone $baseQuery)->where('status', TrainerBooking::STATUS_COMPLETED)->count(),
            'cancelledCount' => (clone $baseQuery)->whereIn('status', [TrainerBooking::STATUS_CANCELLED, TrainerBooking::STATUS_DECLINED])->count(),
            'calendarBookings' => (clone $baseQuery)
                ->scheduled()
                ->whereBetween('confirmed_start_at', [$calendarMonth->copy()->startOfMonth(), $calendarMonth->copy()->endOfMonth()])
                ->orderBy('confirmed_start_at')
                ->get()
                ->groupBy(fn (TrainerBooking $booking) => $booking->confirmed_start_at->day),
            'calendarMonth' => $calendarMonth,
            'filters' => $filters,
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
        Request $request,
        TrainerBooking $trainerBooking,
        SessionNotificationService $notifications,
    ): RedirectResponse {
        abort_unless($trainerBooking->trainerProfile?->user_id === $request->user()->id, 403);
        $notifications->remindTrainerBooking($trainerBooking);

        return back()->with('status', 'Session reminder recorded and sent through the available channels.');
    }
}
