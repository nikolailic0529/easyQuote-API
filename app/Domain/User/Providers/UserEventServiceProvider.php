<?php

namespace App\Domain\User\Providers;

use App\Domain\User\Listeners\UserEventAuditor;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class UserEventServiceProvider extends EventServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    protected $subscribe = [
        UserEventAuditor::class,
    ];
}
