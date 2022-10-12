<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerOpportunityIntegration;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\OpportunityEntity;
use App\Integrations\Pipeliner\Models\OpportunityFilterInput;
use App\Integrations\Pipeliner\Models\SalesUnitFilterInput;
use App\Models\Opportunity;
use App\Models\SalesUnit;
use App\Services\Pipeliner\Exceptions\MultiplePipelinerEntitiesFoundException;
use Illuminate\Support\LazyCollection;

class PipelinerOpportunityLookupService
{
    public function __construct(
        protected PipelinerOpportunityIntegration $integration,
        protected CachedDataEntityResolver $dataEntityResolver
    ) {
    }

    /**
     * @throws MultiplePipelinerEntitiesFoundException
     */
    public function find(Opportunity $opportunity, SalesUnit $unit): ?OpportunityEntity
    {
        $filter = OpportunityFilterInput::new()
            ->unit(SalesUnitFilterInput::new()->name(
                EntityFilterStringField::eq($unit->unit_name)
            ))
            ->name(EntityFilterStringField::ieq($opportunity->project_name));

        $iterator = $this->integration->scroll(
            filter: $filter
        );

        $entities = LazyCollection::make(static function () use ($iterator): \Generator {
            yield from $iterator;
        })
            ->take(2)
            ->all();

        if (empty($entities)) {
            return null;
        }

        if (count($entities) > 1) {
            throw MultiplePipelinerEntitiesFoundException::opportunity($opportunity->project_name, $unit->unit_name);
        }

        return array_shift($entities);
    }
}