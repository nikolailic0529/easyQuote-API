<?php

namespace App\Domain\Worldwide\Listeners;

use App\Domain\Company\Events\CompanyUpdated;
use App\Domain\Worldwide\Services\Opportunity\OpportunityEntityService;

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
