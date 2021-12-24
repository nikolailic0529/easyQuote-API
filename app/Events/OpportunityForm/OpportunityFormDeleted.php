<?php

namespace App\Events\OpportunityForm;

use App\Models\OpportunityForm\OpportunityForm;
use Illuminate\Queue\SerializesModels;

final class OpportunityFormDeleted
{
    use SerializesModels;

    private OpportunityForm $opportunityForm;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\OpportunityForm\OpportunityForm $opportunityForm
     * @return void
     */
    public function __construct(OpportunityForm $opportunityForm)
    {
        //
        $this->opportunityForm = $opportunityForm;
    }

    /**
     * @return \App\Models\OpportunityForm\OpportunityForm
     */
    public function getOpportunityForm(): OpportunityForm
    {
        return $this->opportunityForm;
    }
}
