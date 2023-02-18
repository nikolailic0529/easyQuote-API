<?php

namespace App\Domain\Worldwide\Providers;

use App\Domain\Worldwide\Events\Opportunity\OpportunityUpdated;
use App\Domain\Worldwide\Listeners\OpportunityEventAuditor;
use App\Domain\Worldwide\Listeners\SyncWorldwideContractQuoteWithOpportunityData;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class OpportunityEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        OpportunityEventAuditor::class,
    ];

    protected $listen = [
        OpportunityUpdated::class => [
            SyncWorldwideContractQuoteWithOpportunityData::class,
        ],
    ];
}
