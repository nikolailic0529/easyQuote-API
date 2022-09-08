<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailLimitExceededMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly int $limit,
        public readonly int $remaining,
    ) {
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this
            ->to(setting('failure_report_recipients')->pluck('email')->all())
            ->subject(__('mail.subjects.mail_limit_exceeded'))
            ->markdown('emails.mail-limit-exceeded', [
                'limit' => $this->limit,
                'remaining' => $this->remaining,
            ]);
    }

    public function buildViewData(): array
    {
        return parent::buildViewData() + ['__mailable' => static::class];
    }
}
