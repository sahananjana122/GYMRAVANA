<?php

namespace App\Notifications;

use App\Models\MembershipSubscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipExpiryNotification extends Notification
{
    use Queueable;

    public function __construct(
        public MembershipSubscription $subscription,
        public int $daysRemaining,
    ) {}

    public function via(object $notifiable): array
    {
        return $notifiable instanceof User ? ['database', 'mail'] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->subscription->loadMissing('tier');
        $dayLabel = $this->daysRemaining === 1 ? 'day' : 'days';

        return (new MailMessage)
            ->subject('Your GymRAVANA membership expires in '.$this->daysRemaining.' '.$dayLabel)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your GymRAVANA membership is approaching its expiry date.')
            ->line('Membership number: '.$notifiable->memberProfile->membership_number)
            ->line('Current package: '.$this->subscription->tier->name)
            ->line('Expiry date: '.$this->subscription->ends_on->format('d F Y'))
            ->line('Time remaining: '.$this->daysRemaining.' '.$dayLabel)
            ->action('Review membership', route('member.dashboard'))
            ->line('Please renew online or contact GymRAVANA if you need help choosing your next package.');
    }

    public function toArray(object $notifiable): array
    {
        $this->subscription->loadMissing('tier');

        return [
            'event' => 'membership_expiry_reminder',
            'title' => 'Membership expires in '.$this->daysRemaining.' '.($this->daysRemaining === 1 ? 'day' : 'days'),
            'summary' => $this->subscription->tier->name.' expires on '.$this->subscription->ends_on->format('d M Y').'. Renew or contact GymRAVANA.',
            'membership_number' => $notifiable->memberProfile->membership_number,
            'membership_tier' => $this->subscription->tier->name,
            'ends_on' => $this->subscription->ends_on->toDateString(),
            'days_remaining' => $this->daysRemaining,
            'url' => route('member.dashboard'),
        ];
    }
}
