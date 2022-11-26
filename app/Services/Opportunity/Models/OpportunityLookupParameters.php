<?php

namespace App\Services\Opportunity\Models;

use App\Models\Opportunity;
use Spatie\LaravelData\Data;

final class OpportunityLookupParameters extends Data
{
    public function __construct(
        public readonly string $project_name,
        public readonly ?string $unit_name,
    ) {
    }

    public static function fromModel(Opportunity $opportunity): OpportunityLookupParameters
    {
        return new OpportunityLookupParameters(
            project_name: $opportunity->project_name,
            unit_name: $opportunity->salesUnit?->unit_name,
        );
    }
}