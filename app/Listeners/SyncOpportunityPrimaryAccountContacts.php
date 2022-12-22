<?php

namespace App\Listeners;

use App\Events\Company\CompanyUpdated;
use App\Services\Opportunity\OpportunityEntityService;

class SyncOpportunityPrimaryAccountContacts
{
    public function __construct(
        protected readonly OpportunityEntityService $opportunityEntityService
    ) {
    }

    public function handle(CompanyUpdated $event): void
    {
        $this->opportunityEntityService->syncPrimaryAccountContacts($event->company);
    }
}
