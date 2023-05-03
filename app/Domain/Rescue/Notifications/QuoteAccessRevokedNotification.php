<?php

namespace App\Domain\Rescue\Notifications;

use App\Domain\Priority\Enum\Priority;
use App\Domain\Rescue\Models\Quote;
use App\Domain\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteAccessRevokedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected readonly User $causer,
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
            ->line($this->getMessage())
            ->line('Thank you for using our application!');
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'message' => $this->getMessage(),
            'priority' => Priority::Low,
            'url' => ui_route('quotes.status', ['quote' => $this->quote]),
        ];
    }

    protected function getMessage(): string
    {
        return sprintf(
            'User [%s] has revoked your access to Quote RFQ [%s]',
            $this->causer->email,
            $this->quote->customer->rfq
        );
    }
}
