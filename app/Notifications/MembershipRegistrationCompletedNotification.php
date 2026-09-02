<?php

namespace App\Notifications;

use App\Models\MembershipSubscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class MembershipRegistrationCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(public MembershipSubscription $subscription) {}

    public function via(object $notifiable): array
    {
        return $notifiable instanceof User ? ['database', 'mail'] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->subscription->loadMissing('tier');

        return (new MailMessage)
            ->subject('GymRAVANA registration and membership confirmed')
            ->greeting('Welcome to GymRAVANA, '.$notifiable->name.'!')
            ->line('Your registration and development/demo membership payment have been completed successfully.')
            ->line('Membership number: '.$notifiable->memberProfile->membership_number)
            ->line('Package: '.$this->subscription->tier->name)
            ->line('Start date: '.$this->subscription->starts_on->format('d F Y'))
            ->line('Expiry date: '.$this->subscription->ends_on->format('d F Y'))
            ->action('Verify your email address', $this->verificationUrl($notifiable))
            ->line('This secure verification link confirms that this email address belongs to you.');
    }

    public function toArray(object $notifiable): array
    {
        $this->subscription->loadMissing('tier');

        return [
            'event' => 'membership_registration_completed',
            'title' => 'Membership activated',
            'summary' => 'Your '.$this->subscription->tier->name.' membership is active until '.$this->subscription->ends_on->format('d M Y').'.',
            'membership_number' => $notifiable->memberProfile->membership_number,
            'membership_tier' => $this->subscription->tier->name,
            'starts_on' => $this->subscription->starts_on->toDateString(),
            'ends_on' => $this->subscription->ends_on->toDateString(),
            'url' => route('member.dashboard'),
        ];
    }

    private function verificationUrl(User $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );
    }
}
