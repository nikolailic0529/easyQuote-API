<?php

namespace App\Domain\Rescue\Providers;

use App\Domain\Rescue\Listeners\RescueQuoteEventAuditor;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class QuoteEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        RescueQuoteEventAuditor::class,
    ];
}
