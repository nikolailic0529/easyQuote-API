<?php

namespace App\Mail;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class FailureReportMail extends Mailable
{
    // use SerializesModels;

    /**
     * Exception instance.
     *
     * @var \Exception
     */
    protected $exception;

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
    public function __construct(Exception $exception, Collection $recepients)
    {
        $this->exception = $exception;
        $this->recepients = $recepients;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $message = $this->exception->getMessage();
        $trace = $this->exception->getTraceAsString();

        return $this->to($this->recepients)
            ->subject(__('mail.subjects.failure'))
            ->markdown('emails.failure')
            ->with(compact('message', 'trace'));
    }
}
