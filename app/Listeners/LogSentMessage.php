<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\InteractsWithQueue;
use Mail;

class LogSentMessage
{
    /**
     * The array of failed recipients
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
     * @param  MessageSent  $event
     * @return void
     */
    public function handle(MessageSent $event)
    {
        filled($this->failures) && logger()->error($this->failures);
    }
}
