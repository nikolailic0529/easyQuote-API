<?php

namespace App\Domain\Team\Providers;

use App\Domain\Team\Listeners\TeamEventSubscriber;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class TeamEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        TeamEventSubscriber::class,
    ];
}
