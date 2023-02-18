<?php

namespace App\Domain\Rescue\Notifications;

use App\Domain\Priority\Enum\Priority;
use App\Domain\Rescue\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractDeletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected readonly Contract $contract
    ) {
    }

    public function via(mixed $notifiable): array
    {
        return ['mail', 'database.custom'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->success()
            ->line($this->getMessage())
            ->line('Thank you for using our application!');
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'message' => $this->getMessage(),
            'priority' => Priority::Medium,
            'url' => ui_route('users.notifications'),
            'subject_id' => $this->contract->getKey(),
            'subject_type' => $this->contract->getMorphClass(),
        ];
    }

    protected function getMessage(): string
    {
        return sprintf('Contract [%s] has been deleted.', $this->contract->contract_number);
    }
}
