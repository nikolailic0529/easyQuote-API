<?php

namespace App\Domain\Address\Providers;

use App\Domain\Address\Listeners\AddressEventAuditor;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class AddressEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        AddressEventAuditor::class,
    ];
}
