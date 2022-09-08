<?php

namespace App\Events\Mail;

use Illuminate\Queue\SerializesModels;

final class MailLimitExceeded
{
    use SerializesModels;

    public function __construct()
    {
    }
}
