<?php

namespace App\Domain\Worldwide\Providers;

use App\Domain\Worldwide\Events\Quote\WorldwideQuoteSubmitted;
use App\Domain\Worldwide\Listeners\MigrateWorldwideQuoteAssets;
use App\Domain\Worldwide\Listeners\WorldwideQuoteEventAuditor;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class WorldwideQuoteEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        WorldwideQuoteEventAuditor::class,
    ];

    protected $listen = [
        WorldwideQuoteSubmitted::class => [
            MigrateWorldwideQuoteAssets::class,
        ],
    ];
}
