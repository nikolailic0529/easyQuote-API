<?php

namespace App\Domain\Pipeliner\Services;

use App\Domain\Pipeliner\Integration\GraphQl\PipelinerOpportunityIntegration;
use App\Domain\Pipeliner\Integration\Models\EntityFilterStringField;
use App\Domain\Pipeliner\Integration\Models\OpportunityEntity;
use App\Domain\Pipeliner\Integration\Models\OpportunityFilterInput;
use App\Domain\Pipeliner\Integration\Models\SalesUnitFilterInput;
use App\Domain\Pipeliner\Services\Exceptions\MultiplePipelinerEntitiesFoundException;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Worldwide\Models\Opportunity;
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
