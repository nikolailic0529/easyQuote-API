<?php

namespace App\Domain\Template\Events\OpportunityForm;

use App\Domain\Worldwide\Models\OpportunityForm;
use Illuminate\Queue\SerializesModels;

final class OpportunityFormDeleted
{
    use SerializesModels;

    private \App\Domain\Worldwide\Models\OpportunityForm $opportunityForm;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(OpportunityForm $opportunityForm)
    {
        $this->opportunityForm = $opportunityForm;
    }

    public function getOpportunityForm(): OpportunityForm
    {
        return $this->opportunityForm;
    }
}
