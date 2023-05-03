<?php

namespace App\Foundation\Mail\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Mail;

class LogSentMessage
{
    /**
     * The array of failed recipients.
     *
     * @var array
     */
    protected $failures;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        $this->failures = Mail::failures();
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(MessageSent $event)
    {
        filled($this->failures) && logger()->error($this->failures);
    }
}
