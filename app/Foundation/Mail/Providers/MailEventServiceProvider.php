<?php

namespace App\Foundation\Mail\Providers;

use App\Foundation\Mail\Listeners\LogSentMessage;
use App\Foundation\Mail\Listeners\MailEventRateLimitingSubscriber;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Mail\Events\MessageSent;

class MailEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        MailEventRateLimitingSubscriber::class,
    ];

    protected $listen = [
        MessageSent::class => [
            LogSentMessage::class,
        ],
    ];
}
