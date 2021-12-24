<?php

namespace App\Events\Opportunity;

use App\Contracts\WithCauserEntity;
use App\Contracts\WithOpportunityEntity;
use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

final class OpportunityCreated implements WithOpportunityEntity, WithCauserEntity
{
    use SerializesModels;

    public function __construct(private Opportunity $opportunity,
                                private ?Model      $causer = null)
    {
    }

    /**
     * @return Opportunity
     */
    public function getOpportunity(): Opportunity
    {
        return $this->opportunity;
    }

    public function getCauser(): ?Model
    {
        return $this->causer;
    }
}
