<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use App\Repositories\System\Failure\FailureHelp;
use Illuminate\Database\Eloquent\Collection;

class FailureReportMail extends Mailable
{
    /**
     * FailureHelp instance.
     *
     * @var \App\Repositories\System\Failure\FailureHelp
     */
    protected $failure;

    /**
     * Failure Report recepients.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $recepients;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(FailureHelp $failure, Collection $recepients)
    {
        $this->failure = $failure;
        $this->recepients = $recepients;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->to($this->recepients)
            ->subject(__('mail.subjects.failure'))
            ->markdown('emails.failure')
            ->with(['failure' => $this->failure]);
    }
}
