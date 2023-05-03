<?php

namespace App\Domain\Task\Notifications;

use App\Domain\Priority\Enum\Priority;
use App\Domain\Task\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RevokedInvitationFromTaskNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Task $task
    ) {
    }

    public function via(mixed $notifiable): array
    {
        return ['mail', 'database.custom'];
    }

    public function shouldSend(mixed $notifiable, string $channel): bool
    {
        return true;
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Activity')
            ->line($this->getMessage())
            ->line('Thank you for using our application!');
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'message' => $this->getMessage(),
            'priority' => Priority::Low,
            'subject_type' => $this->task->getMorphClass(),
            'subject_id' => $this->task->getKey(),
        ];
    }

    protected function getMessage(): string
    {
        return sprintf(
            'You have been revoked from %s [%s]',
            $this->task->activity_type->name,
            $this->task->name,
        );
    }
}
