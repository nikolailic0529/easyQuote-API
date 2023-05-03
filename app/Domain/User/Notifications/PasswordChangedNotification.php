<?php

namespace App\Domain\User\Notifications;

use App\Domain\Priority\Enum\Priority;
use App\Domain\User\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification
{
    public function via(mixed $notifiable): array
    {
        return ['mail', 'database.custom'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage())
            ->success()
            ->greeting("Hi $notifiable->user_fullname")
            ->line($this->getMessage())
            ->action(__('Profile'), ui_route('users.profile'));
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'message' => $this->getMessage(),
            'priority' => Priority::Medium,
            'subject_type' => $notifiable->getMorphClass(),
            'subject_id' => $notifiable->getKey(),
        ];
    }

    protected function getMessage(): string
    {
        return 'You have successfully changed your password.';
    }
}
