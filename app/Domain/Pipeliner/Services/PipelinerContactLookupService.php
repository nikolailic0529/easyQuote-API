<?php

namespace App\Domain\Pipeliner\Services;

use App\Domain\Address\Models\Address;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerContactIntegration;
use App\Domain\Pipeliner\Integration\Models\ContactEntity;
use App\Domain\Pipeliner\Integration\Models\ContactFilterInput;
use App\Domain\Pipeliner\Integration\Models\EntityFilterStringField;
use App\Domain\Pipeliner\Services\Exceptions\MultiplePipelinerEntitiesFoundException;

class PipelinerContactLookupService
{
    public function __construct(protected PipelinerContactIntegration $integration)
    {
    }

    public function find(Address $address): ?ContactEntity
    {
        $entities = $this->integration->getByCriteria(
            filter: ContactFilterInput::new()
                ->address(EntityFilterStringField::ieq((string) $address->address_1))
                ->country(EntityFilterStringField::ieq((string) $address->country?->name))
                ->city(EntityFilterStringField::ieq((string) $address->city))
                ->stateProvince(EntityFilterStringField::ieq((string) $address->state))
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
