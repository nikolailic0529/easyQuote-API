<?php

namespace App\Domain\Worldwide\Events\Opportunity;

use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Database\Eloquent\Model;

final class OpportunityOwnershipChanged
{
    public bool $afterCommit = true;

    public function __construct(
        public readonly Opportunity $opportunity,
        public readonly Opportunity $oldOpportunity,
        public readonly ?Model $causer = null
    ) {
    }
}
