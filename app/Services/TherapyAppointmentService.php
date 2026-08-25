<?php

namespace App\Services;

use App\Models\TherapyAppointment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TherapyAppointmentService
{
    public function __construct(private SessionNotificationService $notifications) {}

    public function updateSchedule(TherapyAppointment $appointment, array $data, User $actor): TherapyAppointment
    {
        [$updatedAppointment, $notificationEvent] = DB::transaction(function () use ($appointment, $data, $actor): array {
            $lockedAppointment = TherapyAppointment::query()->lockForUpdate()->findOrFail($appointment->id);
            $before = $this->notificationState($lockedAppointment);
            $isConfirmation = $data['status'] === TherapyAppointment::STATUS_CONFIRMED;
            $containsSchedule = collect([
                'confirmed_start_at',
                'duration_minutes',
                'required_arrival_at',
                'preparation_instructions',
                'specialist_message',
            ])->contains(fn (string $field) => array_key_exists($field, $data));

            if ($isConfirmation) {
                $data['confirmed_at'] = $lockedAppointment->confirmed_at ?? now();
            }

            if ($isConfirmation || $containsSchedule) {
                $data['scheduled_by'] = $actor->id;
            }

            $lockedAppointment->update($data);
            $lockedAppointment->refresh();

            return [$lockedAppointment, $this->notificationEvent($before, $lockedAppointment)];
        });

        if ($notificationEvent) {
            $this->notifications->sendTherapyUpdate($updatedAppointment, $notificationEvent);
        }

        return $updatedAppointment;
    }

    private function notificationFields(): array
    {
        return [
            'status',
            'confirmed_start_at',
            'duration_minutes',
            'required_arrival_at',
            'preparation_instructions',
            'specialist_message',
        ];
    }

    private function notificationEvent(array $before, TherapyAppointment $appointment): ?string
    {
        $after = $this->notificationState($appointment);

        if ($before === $after) {
            return null;
        }

        if ($before['status'] !== TherapyAppointment::STATUS_CONFIRMED
            && $appointment->status === TherapyAppointment::STATUS_CONFIRMED) {
            return 'confirmation';
        }

        if ($before['status'] === TherapyAppointment::STATUS_CONFIRMED
            || $appointment->status === TherapyAppointment::STATUS_CONFIRMED) {
            return 'update';
        }

        return null;
    }

    private function notificationState(TherapyAppointment $appointment): array
    {
        return collect($this->notificationFields())
            ->mapWithKeys(fn (string $field): array => [$field => $appointment->getRawOriginal($field)])
            ->all();
    }
}
