<?php

namespace App\Domain\Worldwide\Events\Opportunity;

use App\Domain\Authentication\Contracts\WithCauserEntity;
use App\Domain\Worldwide\Contracts\WithOpportunityEntity;
use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

final class OpportunityCreated implements WithOpportunityEntity, WithCauserEntity
{
    use SerializesModels;

    public function __construct(private Opportunity $opportunity,
                                private ?Model $causer = null)
    {
    }

    public function getOpportunity(): Opportunity
    {
        return $this->opportunity;
    }

    public function getCauser(): ?Model
    {
        return $this->causer;
    }
}
