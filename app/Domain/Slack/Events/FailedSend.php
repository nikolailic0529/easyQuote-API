<?php

namespace App\Domain\Slack\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FailedSend
{
    use Dispatchable;
    use SerializesModels;

    public $payload;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($payload = null)
    {
        $this->payload = $payload;
    }
}
