<?php

namespace App\Domain\Rescue\Notifications;

use App\Domain\Priority\Enum\Priority;
use App\Domain\Rescue\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected readonly Quote $quote
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
            ->action('Quotation', ui_route('quotes.submitted.review', ['quote' => $this->quote]))
            ->line('Thank you for using our application!');
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'message' => $this->getMessage(),
            'priority' => Priority::Medium,
            'url' => ui_route('quotes.submitted.review', ['quote' => $this->quote]),
            'subject_id' => $this->quote->getKey(),
            'subject_type' => $this->quote->getMorphClass(),
        ];
    }

    protected function getMessage(): string
    {
        return sprintf('Quote [%s] has been has been submitted.', $this->quote->customer->rfq);
    }
}
