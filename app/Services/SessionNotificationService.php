<?php

namespace App\Services;

use App\Models\TherapyAppointment;
use App\Models\TrainerBooking;
use App\Notifications\SessionScheduleNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

class SessionNotificationService
{
    private const REMINDER_COOLDOWN_MINUTES = 5;

    public function sendTrainerUpdate(TrainerBooking $booking, string $event): void
    {
        $booking->loadMissing(['member.memberProfile', 'trainerProfile.user']);

        $this->notifyUser($booking->member, new SessionScheduleNotification(
            $this->trainerPayload($booking, $event),
            $event,
        ));
    }

    public function sendTherapyUpdate(TherapyAppointment $appointment, string $event): void
    {
        $appointment->loadMissing(['user', 'specialist', 'treatment']);
        $notification = new SessionScheduleNotification($this->therapyPayload($appointment, $event), $event);

        if ($appointment->user) {
            $this->notifyUser($appointment->user, $notification);

            return;
        }

        if (filled($appointment->contact_email)) {
            try {
                Notification::route('mail', [$appointment->contact_email => $appointment->customer_name])
                    ->notify($notification);
            } catch (Throwable $exception) {
                Log::warning('Therapy appointment email notification could not be delivered.', [
                    'appointment_id' => $appointment->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }
    }

    public function remindTrainerBooking(TrainerBooking $booking): TrainerBooking
    {
        $this->ensureReminderCanBeSent(
            $booking->status === TrainerBooking::STATUS_ACCEPTED && $booking->isScheduled(),
            $booking->last_reminder_sent_at,
        );

        $this->sendTrainerUpdate($booking, 'reminder');
        $booking->forceFill([
            'last_reminder_sent_at' => now(),
            'reminder_count' => $booking->reminder_count + 1,
        ])->save();

        return $booking->refresh();
    }

    public function remindTherapyAppointment(TherapyAppointment $appointment): TherapyAppointment
    {
        $this->ensureReminderCanBeSent(
            $appointment->status === TherapyAppointment::STATUS_CONFIRMED && $appointment->isScheduled(),
            $appointment->last_reminder_sent_at,
        );

        if (! $appointment->user && ! filled($appointment->contact_email)) {
            throw ValidationException::withMessages([
                'reminder' => 'This client has no linked account or email address. Use the WhatsApp click-to-chat link if a phone number is available.',
            ]);
        }

        $this->sendTherapyUpdate($appointment, 'reminder');
        $appointment->forceFill([
            'last_reminder_sent_at' => now(),
            'reminder_count' => $appointment->reminder_count + 1,
        ])->save();

        return $appointment->refresh();
    }

    public function trainerWhatsAppUrl(TrainerBooking $booking): ?string
    {
        $booking->loadMissing(['member.memberProfile', 'trainerProfile.user']);

        return $this->whatsAppUrl(
            $booking->member?->memberProfile?->phone,
            $this->whatsAppMessage($this->trainerPayload($booking, 'reminder')),
        );
    }

    public function therapyWhatsAppUrl(TherapyAppointment $appointment): ?string
    {
        $appointment->loadMissing(['specialist', 'treatment']);

        return $this->whatsAppUrl(
            $appointment->contact_phone,
            $this->whatsAppMessage($this->therapyPayload($appointment, 'reminder')),
        );
    }

    private function notifyUser(?object $user, SessionScheduleNotification $notification): void
    {
        if (! $user) {
            return;
        }

        try {
            $user->notify($notification);
        } catch (Throwable $exception) {
            Log::warning('Session notification could not be fully delivered.', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function trainerPayload(TrainerBooking $booking, string $event): array
    {
        $provider = $booking->trainerProfile?->user?->name ?? 'GymRAVANA trainer';

        return $this->payload(
            event: $event,
            clientName: $booking->member?->name ?? 'Member',
            provider: $provider,
            sessionType: $booking->program_type,
            status: $booking->status,
            dateTime: $booking->confirmed_start_at,
            arrivalTime: $booking->required_arrival_at,
            duration: $booking->duration_minutes,
            instructions: $booking->preparation_instructions,
            providerMessage: $booking->trainer_message,
            url: route('member.dashboard'),
            reference: 'Trainer booking #'.$booking->id,
        );
    }

    private function therapyPayload(TherapyAppointment $appointment, string $event): array
    {
        return $this->payload(
            event: $event,
            clientName: $appointment->customer_name,
            provider: $appointment->specialist?->name ?? 'GymRAVANA therapist',
            sessionType: $appointment->treatment?->name ?? 'Therapy consultation',
            status: $appointment->status,
            dateTime: $appointment->confirmed_start_at,
            arrivalTime: $appointment->required_arrival_at,
            duration: $appointment->duration_minutes,
            instructions: $appointment->preparation_instructions,
            providerMessage: $appointment->specialist_message,
            url: route('therapy-appointments.success', $appointment),
            reference: 'Therapy appointment '.$appointment->appointment_number,
        );
    }

    private function payload(
        string $event,
        string $clientName,
        string $provider,
        string $sessionType,
        string $status,
        mixed $dateTime,
        mixed $arrivalTime,
        ?int $duration,
        ?string $instructions,
        ?string $providerMessage,
        string $url,
        string $reference,
    ): array {
        $eventLabel = match ($event) {
            'confirmation' => 'confirmed',
            'reminder' => 'reminder',
            default => 'updated',
        };
        $title = $event === 'reminder'
            ? 'Reminder: your GymRAVANA session is coming up'
            : 'Your GymRAVANA session was '.$eventLabel;

        return [
            'title' => $title,
            'summary' => "Your {$sessionType} session is {$status}.",
            'client_name' => $clientName,
            'provider' => $provider,
            'session_type' => $sessionType,
            'status' => $status,
            'date_time' => $dateTime?->format('D, d M Y \a\t H:i') ?? 'Not scheduled',
            'arrival_time' => $arrivalTime?->format('D, d M Y \a\t H:i') ?? 'Not specified',
            'duration' => $duration ? $duration.' minutes' : 'Not specified',
            'instructions' => $instructions,
            'provider_message' => $providerMessage,
            'url' => $url,
            'reference' => $reference,
        ];
    }

    private function ensureReminderCanBeSent(bool $isConfirmedAndScheduled, mixed $lastSentAt): void
    {
        if (! $isConfirmedAndScheduled) {
            throw ValidationException::withMessages([
                'reminder' => 'A reminder can only be sent for a confirmed, scheduled session.',
            ]);
        }

        if ($lastSentAt?->greaterThan(now()->subMinutes(self::REMINDER_COOLDOWN_MINUTES))) {
            throw ValidationException::withMessages([
                'reminder' => 'Please wait five minutes before sending another reminder for this session.',
            ]);
        }
    }

    private function whatsAppMessage(array $payload): string
    {
        $message = "GymRAVANA session reminder\n"
            .$payload['session_type'].' with '.$payload['provider']."\n"
            .$payload['date_time']."\n"
            .'Please arrive: '.$payload['arrival_time']."\n"
            .'Duration: '.$payload['duration'];

        if (filled($payload['instructions'])) {
            $message .= "\nPreparation: ".$payload['instructions'];
        }

        return $message;
    }

    private function whatsAppUrl(?string $phone, string $message): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone ?? '');

        if (! $digits) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '94'.substr($digits, 1);
        }

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
    }
}
