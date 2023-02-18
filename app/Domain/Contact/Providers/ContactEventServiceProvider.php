<?php

namespace App\Domain\Contact\Providers;

use App\Domain\Contact\Listeners\ContactEventAuditor;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class ContactEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        ContactEventAuditor::class,
    ];
}
