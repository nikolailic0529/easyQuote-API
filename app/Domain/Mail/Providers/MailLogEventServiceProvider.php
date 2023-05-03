<?php

namespace App\Domain\Mail\Providers;

use App\Domain\Mail\Listeners\RecordMessageSending;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Mail\Events\MessageSending;

final class MailLogEventServiceProvider extends EventServiceProvider
{
    protected $listen = [
        MessageSending::class => [
            RecordMessageSending::class,
        ],
    ];
}
