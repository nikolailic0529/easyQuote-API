<?php

namespace App\Domain\Worldwide\Notifications;

use App\Domain\Priority\Enum\Priority;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorldwideQuoteUnraveledNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected readonly WorldwideQuote $quote
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
            ->action('Quotation', $this->getUrl())
            ->line('Thank you for using our application!');
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'message' => $this->getMessage(),
            'priority' => Priority::Medium,
            'url' => $this->getUrl(),
            'subject_id' => $this->quote->getKey(),
            'subject_type' => $this->quote->getMorphClass(),
        ];
    }

    protected function getUrl(): string
    {
        $route = match ($this->quote->contractType()->getParentKey()) {
            CT_PACK => 'ww-quotes.submitted.pk-preview',
            CT_CONTRACT => 'ww-quotes.submitted.ct-preview',
        };

        return ui_route($route, ['opportunity' => $this->quote->opportunity, 'quote' => $this->quote]);
    }

    protected function getMessage(): string
    {
        return sprintf('Quote [%s] has been has been moved to drafted.', $this->quote->quote_number);
    }
}
