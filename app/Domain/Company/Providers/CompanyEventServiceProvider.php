<?php

namespace App\Domain\Company\Providers;

use App\Domain\Company\Events\CompanyUpdated;
use App\Domain\Company\Listeners\CompanyEventAuditor;
use App\Domain\Worldwide\Listeners\SyncOpportunityPrimaryAccountContacts;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class CompanyEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        CompanyEventAuditor::class,
    ];

    protected $listen = [
        CompanyUpdated::class => [
            SyncOpportunityPrimaryAccountContacts::class,
        ],
    ];
}
