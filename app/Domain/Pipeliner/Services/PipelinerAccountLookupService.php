<?php

namespace App\Domain\Pipeliner\Services;

use App\Domain\Company\Models\Company;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerAccountIntegration;
use App\Domain\Pipeliner\Integration\Models\AccountEntity;
use App\Domain\Pipeliner\Integration\Models\AccountFilterInput;
use App\Domain\Pipeliner\Integration\Models\EntityFilterStringField;
use App\Domain\Pipeliner\Integration\Models\SalesUnitFilterInput;
use App\Domain\Pipeliner\Services\Exceptions\MultiplePipelinerEntitiesFoundException;

class PipelinerAccountLookupService
{
    public function __construct(protected PipelinerAccountIntegration $integration,
                                protected CachedDataEntityResolver $dataEntityResolver)
    {
    }

    /**
     * @throws MultiplePipelinerEntitiesFoundException
     */
    public function find(Company $company, array $units): ?AccountEntity
    {
        $filter = AccountFilterInput::new()
            ->name(EntityFilterStringField::ieq($company->name))
            ->unit(SalesUnitFilterInput::new()->name(
                EntityFilterStringField::eq(...collect($units)->pluck('unit_name'))
            ));

        $entities = $this->integration->getByCriteria(
            filter: $filter
        );

        if (empty($entities)) {
            return null;
        }

        if (count($entities) > 1) {
            throw new MultiplePipelinerEntitiesFoundException();
        }

        return array_shift($entities);
    }
}
