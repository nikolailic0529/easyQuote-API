<?php

namespace App\Domain\Worldwide\Notifications;

use App\Domain\Priority\Enum\Priority;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorldwideQuoteOwnershipChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly WorldwideQuote $quote
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
            'subject_type' => $this->quote->getMorphClass(),
            'subject_id' => $this->quote->getKey(),
        ];
    }

    protected function getMessage(): string
    {
        return sprintf(
            'You are the new owner of the [%s] quote',
            $this->quote->quote_number,
        );
    }
}
