<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerAccountIntegration;
use App\Integrations\Pipeliner\Models\AccountEntity;
use App\Integrations\Pipeliner\Models\AccountFilterInput;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Models\Company;
use App\Services\Pipeliner\Exceptions\MultiplePipelinerEntitiesFoundException;

class PipelinerAccountLookupService
{
    public function __construct(protected PipelinerAccountIntegration     $integration,
                                protected RuntimeCachedDataEntityResolver $dataEntityResolver)
    {
    }

    /**
     * @throws MultiplePipelinerEntitiesFoundException
     */
    public function find(Company $company): ?AccountEntity
    {
        $filter = AccountFilterInput::new()
            ->name(EntityFilterStringField::ieq($company->name));

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