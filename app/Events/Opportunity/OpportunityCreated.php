<?php

namespace App\Events\Opportunity;

use App\Contracts\WithOpportunityEntity;
use App\Models\Opportunity;
use Illuminate\Queue\SerializesModels;

final class OpportunityCreated implements WithOpportunityEntity
{
    use SerializesModels;

    private Opportunity $opportunity;

    /**
     * Create a new event instance.
     *
     * @param Opportunity $opportunity
     */
    public function __construct(Opportunity $opportunity)
    {
        $this->opportunity = $opportunity;
    }

    /**
     * @return Opportunity
     */
    public function getOpportunity(): Opportunity
    {
        return $this->opportunity;
    }
}
