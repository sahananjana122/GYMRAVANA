<?php

namespace App\Services;

use App\Models\TrainerBooking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TrainerBookingService
{
    public function __construct(
        private SessionNotificationService $notifications,
        private GamificationProgressService $gamification,
    ) {}

    public function updateSchedule(TrainerBooking $booking, array $data, User $actor): TrainerBooking
    {
        [$updatedBooking, $notificationEvent] = DB::transaction(function () use ($booking, $data, $actor): array {
            $lockedBooking = TrainerBooking::query()->lockForUpdate()->findOrFail($booking->id);
            $before = $this->notificationState($lockedBooking);
            $isConfirmation = $data['status'] === TrainerBooking::STATUS_ACCEPTED;
            $containsSchedule = collect([
                'confirmed_start_at',
                'duration_minutes',
                'required_arrival_at',
                'preparation_instructions',
                'trainer_message',
            ])->contains(fn (string $field) => array_key_exists($field, $data));

            if ($isConfirmation) {
                $data['confirmed_at'] = $lockedBooking->confirmed_at ?? now();
            }

            if ($isConfirmation || $containsSchedule) {
                $data['scheduled_by'] = $actor->id;
            }

            $lockedBooking->update($data);
            $lockedBooking->refresh();

            return [$lockedBooking, $this->notificationEvent($before, $lockedBooking)];
        });

        if ($notificationEvent) {
            $this->notifications->sendTrainerUpdate($updatedBooking, $notificationEvent);
        }

        if ($updatedBooking->status === TrainerBooking::STATUS_COMPLETED) {
            $this->gamification->syncFor($updatedBooking->member);
        }

        return $updatedBooking;
    }

    private function notificationFields(): array
    {
        return [
            'status',
            'confirmed_start_at',
            'duration_minutes',
            'required_arrival_at',
            'preparation_instructions',
            'trainer_message',
        ];
    }

    private function notificationEvent(array $before, TrainerBooking $booking): ?string
    {
        $after = $this->notificationState($booking);

        if ($before === $after) {
            return null;
        }

        if ($before['status'] !== TrainerBooking::STATUS_ACCEPTED
            && $booking->status === TrainerBooking::STATUS_ACCEPTED) {
            return 'confirmation';
        }

        if ($before['status'] === TrainerBooking::STATUS_ACCEPTED
            || $booking->status === TrainerBooking::STATUS_ACCEPTED) {
            return 'update';
        }

        return null;
    }

    private function notificationState(TrainerBooking $booking): array
    {
        return collect($this->notificationFields())
            ->mapWithKeys(fn (string $field): array => [$field => $booking->getRawOriginal($field)])
            ->all();
    }
}
