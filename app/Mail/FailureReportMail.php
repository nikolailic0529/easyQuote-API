<?php

namespace App\Mail;

use App\Factories\Failure\FailureHelp;
use Illuminate\Mail\Mailable;

class FailureReportMail extends Mailable
{
    protected FailureHelp $failure;

    protected array $recipients;

    /**
     * Create a new message instance.
     *
     * @param FailureHelp $failure
     * @param array $recipients
     */
    public function __construct(FailureHelp $failure, array $recipients)
    {
        $this->failure = $failure;
        $this->recipients = $recipients;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->to($this->recipients)
            ->subject(__('mail.subjects.failure'))
            ->markdown('emails.failure')
            ->with(['failure' => $this->failure]);
    }
}
