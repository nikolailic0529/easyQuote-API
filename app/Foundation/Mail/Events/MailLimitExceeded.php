<?php

namespace App\Foundation\Mail\Events;

use Illuminate\Queue\SerializesModels;

final class MailLimitExceeded
{
    use SerializesModels;

    public function __construct()
    {
    }
}
