<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerContactIntegration;
use App\Integrations\Pipeliner\Models\ContactEntity;
use App\Integrations\Pipeliner\Models\ContactFilterInput;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Models\Address;
use App\Services\Pipeliner\Exceptions\MultiplePipelinerEntitiesFoundException;

class PipelinerContactLookupService
{
    public function __construct(protected PipelinerContactIntegration $integration)
    {
    }

    public function find(Address $address): ?ContactEntity
    {
        $entities = $this->integration->getByCriteria(
            filter: ContactFilterInput::new()
                ->address(EntityFilterStringField::ieq((string)$address->address_1))
                ->country(EntityFilterStringField::ieq((string)$address->country?->name))
                ->city(EntityFilterStringField::ieq((string)$address->city))
                ->stateProvince(EntityFilterStringField::ieq((string)$address->state))
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