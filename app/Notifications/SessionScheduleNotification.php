<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionScheduleNotification extends Notification
{
    use Queueable;

    public function __construct(
        public array $session,
        public string $event,
    ) {}

    public function via(object $notifiable): array
    {
        return $notifiable instanceof User ? ['database', 'mail'] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->session['title'])
            ->greeting('Hello '.$this->session['client_name'].',')
            ->line($this->session['summary'])
            ->line('Provider: '.$this->session['provider'])
            ->line('Session: '.$this->session['session_type'])
            ->line('Date and time: '.$this->session['date_time'])
            ->line('Required arrival: '.$this->session['arrival_time'])
            ->line('Duration: '.$this->session['duration'])
            ->action('View session details', $this->session['url']);

        if (filled($this->session['instructions'])) {
            $mail->line('Preparation: '.$this->session['instructions']);
        }

        if (filled($this->session['provider_message'])) {
            $mail->line('Message from your provider: '.$this->session['provider_message']);
        }

        return $mail->line('If this schedule no longer works for you, please contact GymRAVANA.');
    }

    public function toArray(object $notifiable): array
    {
        return $this->session + ['event' => $this->event];
    }
}
