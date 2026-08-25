<?php

namespace App\Http\Requests;

use App\Models\TherapyAppointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TherapyAppointmentScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $appointment = $this->route('therapyAppointment');

        if (! $user || ! $appointment instanceof TherapyAppointment) {
            return false;
        }

        return $user->hasRole('admin')
            || ($user->hasRole('therapist') && $appointment->specialist?->user_id === $user->id);
    }

    public function rules(): array
    {
        $statuses = $this->routeIs('admin.*')
            ? TherapyAppointment::STATUSES
            : TherapyAppointment::THERAPIST_MANAGED_STATUSES;

        return [
            'status' => ['required', Rule::in($statuses)],
            'confirmed_start_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'between:15,480'],
            'required_arrival_at' => ['nullable', 'date'],
            'preparation_instructions' => ['nullable', 'string', 'max:5000'],
            'specialist_message' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $status = $this->string('status')->toString();

            if (! in_array($status, [TherapyAppointment::STATUS_CONFIRMED, TherapyAppointment::STATUS_COMPLETED], true)) {
                return;
            }

            foreach ([
                'confirmed_start_at' => 'A confirmed appointment time is required.',
                'duration_minutes' => 'An appointment duration is required.',
                'required_arrival_at' => 'A required arrival time is required.',
            ] as $field => $message) {
                if (! filled($this->input($field))) {
                    $validator->errors()->add($field, $message);
                }
            }

            if ($validator->errors()->hasAny(['confirmed_start_at', 'duration_minutes', 'required_arrival_at'])) {
                return;
            }

            $sessionStart = Carbon::parse($this->input('confirmed_start_at'));
            $arrivalTime = Carbon::parse($this->input('required_arrival_at'));

            if ($arrivalTime->greaterThan($sessionStart)) {
                $validator->errors()->add('required_arrival_at', 'The required arrival time must be at or before the appointment start.');
            }

            if ($arrivalTime->diffInMinutes($sessionStart) > 180) {
                $validator->errors()->add('required_arrival_at', 'The arrival time cannot be more than three hours before the appointment.');
            }

            if ($status === TherapyAppointment::STATUS_CONFIRMED && $sessionStart->lessThanOrEqualTo(now())) {
                $validator->errors()->add('confirmed_start_at', 'A confirmed appointment must be scheduled in the future.');
            }

            if ($status === TherapyAppointment::STATUS_COMPLETED && $sessionStart->isFuture()) {
                $validator->errors()->add('confirmed_start_at', 'A future appointment cannot be marked as completed.');
            }

            if ($status !== TherapyAppointment::STATUS_CONFIRMED) {
                return;
            }

            /** @var TherapyAppointment $appointment */
            $appointment = $this->route('therapyAppointment');
            $sessionEnd = $sessionStart->copy()->addMinutes($this->integer('duration_minutes'));
            $conflicts = TherapyAppointment::query()
                ->whereKeyNot($appointment->id)
                ->where('status', TherapyAppointment::STATUS_CONFIRMED)
                ->whereNotNull('confirmed_start_at')
                ->where(function ($query) use ($appointment): void {
                    $query->where('therapy_specialist_id', $appointment->therapy_specialist_id);

                    if ($appointment->user_id) {
                        $query->orWhere('user_id', $appointment->user_id);
                    } elseif (filled($appointment->contact_email)) {
                        $query->orWhere('contact_email', $appointment->contact_email);
                    } elseif (filled($appointment->contact_phone)) {
                        $query->orWhere('contact_phone', $appointment->contact_phone);
                    }
                })
                ->whereBetween('confirmed_start_at', [
                    $sessionStart->copy()->subHours(8),
                    $sessionEnd,
                ])
                ->get()
                ->contains(function (TherapyAppointment $other) use ($sessionStart, $sessionEnd): bool {
                    $otherEnd = $other->confirmed_start_at->copy()->addMinutes($other->duration_minutes ?? 45);

                    return $sessionStart->lessThan($otherEnd) && $sessionEnd->greaterThan($other->confirmed_start_at);
                });

            if ($conflicts) {
                $validator->errors()->add('confirmed_start_at', 'This time overlaps another confirmed appointment for the therapist or client.');
            }
        }];
    }
}
