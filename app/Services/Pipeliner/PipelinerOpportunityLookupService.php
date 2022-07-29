<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerOpportunityIntegration;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\OpportunityEntity;
use App\Integrations\Pipeliner\Models\OpportunityFilterInput;
use App\Integrations\Pipeliner\Models\SalesUnitFilterInput;
use App\Models\Opportunity;
use App\Services\Pipeliner\Exceptions\MultiplePipelinerEntitiesFoundException;
use Illuminate\Support\LazyCollection;

class PipelinerOpportunityLookupService
{
    public function __construct(protected PipelinerOpportunityIntegration $integration,
                                protected RuntimeCachedDataEntityResolver $dataEntityResolver)
    {
    }

    /**
     * @throws MultiplePipelinerEntitiesFoundException
     */
    public function find(Opportunity $opportunity, array $units): ?OpportunityEntity
    {
        $filter = OpportunityFilterInput::new()
            ->unit(SalesUnitFilterInput::new()->name(
                EntityFilterStringField::eq(...collect($units)->pluck('unit_name'))
            ))
            ->name(EntityFilterStringField::ieq($opportunity->project_name));

        $iterator = $this->integration->scroll(
            filter: $filter
        );

        $entities = LazyCollection::make(static fn(): \Generator => yield from $iterator)
            ->filter(function (OpportunityEntity $opp) use ($opportunity): bool {
                $typeData = ($this->dataEntityResolver)($opp->customFields['cfOpportunityTypeId'] ?? null);
                $natureOfServiceData = ($this->dataEntityResolver)($opp->customFields['cfNatureOfService1Id'] ?? null);

                return 0 === strcasecmp((string)$typeData?->optionName, (string)$opportunity->contractType?->type_short_name)
                    && 0 === strcasecmp((string)$natureOfServiceData?->optionName, (string)$opportunity->nature_of_service);
            })
            ->take(2)
            ->all();

        if (empty($entities)) {
            return null;
        }

        if (count($entities) > 1) {
            throw new MultiplePipelinerEntitiesFoundException();
        }

        return array_shift($entities);
    }
}