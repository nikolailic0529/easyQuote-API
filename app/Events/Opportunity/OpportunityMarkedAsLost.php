<?php

namespace App\Events\Opportunity;

use App\Contracts\WithCauserEntity;
use App\Contracts\WithOpportunityEntity;
use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OpportunityMarkedAsLost implements WithOpportunityEntity, WithCauserEntity
{
    use Dispatchable, SerializesModels;

    public function __construct(private Opportunity $opportunity,
                                private ?Model      $causer)
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
