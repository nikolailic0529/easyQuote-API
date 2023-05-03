<?php

namespace App\Domain\Company\Notifications;

use App\Domain\Company\Models\Company;
use App\Domain\Priority\Enum\Priority;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyOwnershipChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Company $company
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
            'subject_type' => $this->company->getMorphClass(),
            'subject_id' => $this->company->getKey(),
        ];
    }

    protected function getMessage(): string
    {
        return sprintf(
            'You are the new owner of the [%s] company',
            $this->company->name,
        );
    }
}
