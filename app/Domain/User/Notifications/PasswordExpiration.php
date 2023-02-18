<?php

namespace App\Domain\User\Notifications;

use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordExpiration extends Notification
{
    protected Carbon $expirationDate;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Carbon $expirationDate)
    {
        $this->expirationDate = $expirationDate;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $user
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($user)
    {
        $expires_at = $this->expirationDate->format('d M Y');

        return (new MailMessage())
                    ->greeting("Hi {$user->fullname}")
                    ->line(__(PWDE_02, compact('expires_at')))
                    ->action('Change Password', ui_route('users.profile'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
        ];
    }
}
