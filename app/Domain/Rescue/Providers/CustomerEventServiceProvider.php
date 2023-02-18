<?php

namespace App\Domain\Rescue\Providers;

use App\Domain\Rescue\Events\Customer\RfqReceived;
use App\Domain\Rescue\Listeners\RfqReceivedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class CustomerEventServiceProvider extends EventServiceProvider
{
    protected $listen = [
        RfqReceived::class => [
            RfqReceivedListener::class,
        ],
    ];
}
