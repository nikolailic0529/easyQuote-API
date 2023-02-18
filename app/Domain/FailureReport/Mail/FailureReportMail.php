<?php

namespace App\Domain\FailureReport\Mail;

use Illuminate\Mail\Mailable;

class FailureReportMail extends Mailable
{
    public function __construct(
        public readonly \Throwable $e
    ) {
    }

    public function build(): static
    {
        return $this->to(setting('failure_report_recipients'))
            ->subject(__('mail.subjects.failure'))
            ->markdown('emails.failure')
            ->with([
                'failure_message' => $this->e->getMessage(),
                'failure_trace' => $this->e->getTraceAsString(),
            ]);
    }
}
