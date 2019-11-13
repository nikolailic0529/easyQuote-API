<?php

namespace App\Mail;

use Exception;
use App\Contracts\Repositories\UserRepositoryInterface as UserRepository;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FailureReportMail extends Mailable
{
    use SerializesModels;

    /**
     * Exception instance.
     *
     * @var \Exception
     */
    protected $exception;

    /**
     * UserRepository
     *
     * @var \App\Repositories\UserRepository
     */
    protected $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Exception $exception)
    {
        $this->exception = $exception;
        $this->user = app(UserRepository::class);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $administrators = $this->user->administrators()
            ->whereNotIn('email', ['chris.cann@supportwarehouse.com', 'rowena.horsfall@supportwarehouse.com']);
        $message = $this->exception->getMessage();
        $trace = $this->exception->getTraceAsString();

        return $this->to($administrators)
            ->subject(__('mail.subjects.failure'))
            ->markdown('emails.failure')
            ->with(compact('message', 'trace'));
    }
}
