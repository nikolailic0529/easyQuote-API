<?php

namespace App\Events\Opportunity;

use App\Models\Opportunity;
use Illuminate\Queue\SerializesModels;

final class OpportunityUpdated
{
    use SerializesModels;

    private Opportunity $opportunity;

    private Opportunity $oldOpportunity;

    /**
     * Create a new event instance.
     *
     * @param Opportunity $opportunity
     * @param Opportunity $oldOpportunity
     */
    public function __construct(Opportunity $opportunity, Opportunity $oldOpportunity)
    {
        $this->opportunity = $opportunity;
        $this->oldOpportunity = $oldOpportunity;
    }

    /**
     * @return Opportunity
     */
    public function getOpportunity(): Opportunity
    {
        return $this->opportunity;
    }

    /**
     * @return Opportunity
     */
    public function getOldOpportunity(): Opportunity
    {
        return $this->oldOpportunity;
    }
}
