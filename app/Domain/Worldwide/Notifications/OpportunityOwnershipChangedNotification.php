<?php

namespace App\Domain\Worldwide\Notifications;

use App\Domain\Priority\Enum\Priority;
use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OpportunityOwnershipChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Opportunity $opportunity
    ) {
    }

    public function via(mixed $notifiable): array
    {
        return ['mail', 'database.custom'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Ownership changed')
            ->line($this->getMessage())
            ->line('Thank you for using our application!');
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'message' => $this->getMessage(),
            'priority' => Priority::Low,
            'subject_type' => $this->opportunity->getMorphClass(),
            'subject_id' => $this->opportunity->getKey(),
        ];
    }

    protected function getMessage(): string
    {
        return sprintf(
            'You are the new owner of the [%s] opportunity',
            $this->opportunity->project_name,
        );
    }
}
