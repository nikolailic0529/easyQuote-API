<?php

namespace App\Domain\User\Notifications;

use App\Domain\Priority\Enum\Priority;
use App\Domain\User\Models\User;
use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordExpiringNotification extends Notification
{
    public function __construct(
        protected Carbon $expirationDate
    ) {
    }

    public function via(mixed $notifiable): array
    {
        return ['mail', 'database.custom'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage())
            ->greeting("Hi $notifiable->user_fullname")
            ->line($this->getMessage())
            ->action(__('Change Password'), ui_route('users.profile'));
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
        return __('Your password is expiring on :expires_at.', ['expires_at' => $this->expirationDate->format('d M Y')]);
    }
}
