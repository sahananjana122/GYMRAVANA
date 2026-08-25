<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TherapyAppointmentScheduleRequest;
use App\Models\TherapyAppointment;
use App\Models\TherapySpecialist;
use App\Services\SessionNotificationService;
use App\Services\TherapyAppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TherapyAppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(TherapyAppointment::STATUSES)],
            'therapy_specialist_id' => ['nullable', 'integer', 'exists:therapy_specialists,id'],
            'date' => ['nullable', 'date'],
        ]);

        return view('admin.therapy-appointments.index', [
            'appointments' => TherapyAppointment::with(['condition', 'treatment', 'specialist.user', 'user', 'scheduler'])
                ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->when($filters['therapy_specialist_id'] ?? null, fn ($query, int|string $id) => $query->where('therapy_specialist_id', (int) $id))
                ->when($filters['date'] ?? null, function ($query, string $date): void {
                    $query->where(function ($query) use ($date): void {
                        $query->whereDate('confirmed_start_at', $date)
                            ->orWhere(function ($query) use ($date): void {
                                $query->whereNull('confirmed_start_at')->whereDate('preferred_datetime', $date);
                            });
                    });
                })
                ->orderByRaw('COALESCE(confirmed_start_at, preferred_datetime) DESC')
                ->paginate(20)
                ->withQueryString(),
            'specialists' => TherapySpecialist::orderBy('name')->get(),
            'filters' => $filters,
        ]);
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
        TherapyAppointment $therapyAppointment,
        SessionNotificationService $notifications,
    ): RedirectResponse {
        $notifications->remindTherapyAppointment($therapyAppointment);

        return back()->with('status', 'Session reminder recorded and sent through the available channels.');
    }
}
