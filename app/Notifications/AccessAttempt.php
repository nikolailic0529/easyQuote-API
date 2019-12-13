<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\AccessAttempt as Attempt;

class AccessAttempt extends Notification
{
    use Queueable;

    protected $attempt;

    public function __construct(Attempt $attempt)
    {
        $this->attempt = $attempt;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($user)
    {
        return (new MailMessage)
                ->greeting("Hi {$user->fullname}")
                ->line("Some one tried to login to your account from ip address: {$this->attempt->ip_address}.")
                ->line("System has prevented this attempt. If it wasn't you then it's highly recommended to change your password.")
                ->line('If it was you, then please logout from your existing session and then try logging in again.');
    }
}
