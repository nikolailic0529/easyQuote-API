<?php

namespace App\Domain\Worldwide\Providers;

use App\Domain\Worldwide\Listeners\SalesOrderEventAuditor;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class SalesOrderEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        SalesOrderEventAuditor::class,
    ];
}
