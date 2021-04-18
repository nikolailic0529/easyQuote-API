<?php

namespace App\Listeners;

use App\Events\Company\CompanyUpdated;
use App\Services\Opportunity\OpportunityEntityService;

class SyncOpportunityPrimaryAccountContacts
{
    protected OpportunityEntityService $opportunityEntityService;

    public function __construct(OpportunityEntityService $opportunityEntityService)
    {
        $this->opportunityEntityService = $opportunityEntityService;
    }

    /**
     * Handle the event.
     *
     * @param CompanyUpdated $event
     * @return void
     */
    public function handle(CompanyUpdated $event)
    {
        $companyModel = $event->getCompany();

        $this->opportunityEntityService->syncPrimaryAccountContacts($companyModel);
    }
}
