<?php

namespace App\Domain\Task\Providers;

use App\Domain\Task\Listeners\TaskEventSubscriber;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class TaskEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        TaskEventSubscriber::class,
    ];
}
