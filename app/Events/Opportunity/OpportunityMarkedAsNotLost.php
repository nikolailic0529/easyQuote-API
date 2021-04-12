<?php

namespace App\Events\Opportunity;

use App\Contracts\WithOpportunityEntity;
use App\Models\Opportunity;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OpportunityMarkedAsNotLost implements WithOpportunityEntity
{
    use Dispatchable, SerializesModels;

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

    public function getOpportunity(): Opportunity
    {
        return $this->opportunity;
    }
}
