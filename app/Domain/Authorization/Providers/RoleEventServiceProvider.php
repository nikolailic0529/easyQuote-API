<?php

namespace App\Domain\Authorization\Providers;

use App\Domain\Authorization\Listeners\RoleEventAuditor;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class RoleEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        RoleEventAuditor::class,
    ];
}
