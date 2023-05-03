<?php

namespace App\Domain\Stats\Providers;

use App\Domain\Stats\Listeners\StatsDependentEntityEventSubscriber;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class StatsEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        StatsDependentEntityEventSubscriber::class,
    ];
}
