<?php

namespace App\Http\Controllers\Therapist;

use App\Http\Controllers\Controller;
use App\Http\Requests\TherapyAppointmentScheduleRequest;
use App\Models\TherapyAppointment;
use App\Services\SessionNotificationService;
use App\Services\TherapyAppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $specialist = $request->user()->therapySpecialist;
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(TherapyAppointment::STATUSES)],
            'date' => ['nullable', 'date'],
        ]);

        $appointments = $specialist
            ? $specialist->appointments()
                ->with(['condition', 'treatment', 'user', 'scheduler'])
                ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->when($filters['date'] ?? null, function ($query, string $date): void {
                    $query->where(function ($query) use ($date): void {
                        $query->whereDate('confirmed_start_at', $date)
                            ->orWhere(function ($query) use ($date): void {
                                $query->whereNull('confirmed_start_at')->whereDate('preferred_datetime', $date);
                            });
                    });
                })
                ->orderByRaw('COALESCE(confirmed_start_at, preferred_datetime) ASC')
                ->paginate(15)
                ->withQueryString()
            : collect();

        return view('therapist.appointments', compact('specialist', 'appointments', 'filters'));
    }

    public function update(
        TherapyAppointmentScheduleRequest $request,
        TherapyAppointment $therapyAppointment,
        TherapyAppointmentService $appointments,
    ): RedirectResponse {
        $appointments->updateSchedule($therapyAppointment, $request->validated(), $request->user());

        return back()->with('status', 'Therapy appointment schedule updated.');
    }

    public function remind(
        Request $request,
        TherapyAppointment $therapyAppointment,
        SessionNotificationService $notifications,
    ): RedirectResponse {
        abort_unless(
            $request->user()->therapySpecialist?->id === $therapyAppointment->therapy_specialist_id,
            403,
        );
        $notifications->remindTherapyAppointment($therapyAppointment);

        return back()->with('status', 'Session reminder recorded and sent through the available channels.');
    }
}
