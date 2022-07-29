<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerSalesUnitIntegration;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\SalesUnitEntity;
use App\Integrations\Pipeliner\Models\SalesUnitFilterInput;
use App\Models\SalesUnit;
use App\Services\Pipeliner\Exceptions\MultiplePipelinerEntitiesFoundException;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\MultipleItemsFoundException;

class PipelinerSalesUnitLookupService
{
    public function __construct(protected PipelinerSalesUnitIntegration $integration)
    {
    }

    /**
     * @throws MultiplePipelinerEntitiesFoundException
     */
    public function find(SalesUnit $unit): ?SalesUnitEntity
    {
        $filter = SalesUnitFilterInput::new()
            ->name(EntityFilterStringField::eq($unit->unit_name));

        $entities = LazyCollection::make($this->integration->getAll(
            filter: $filter
        ));

        try {
            return $entities->sole();
        } catch (MultipleItemsFoundException) {
            throw new MultiplePipelinerEntitiesFoundException();
        } catch (ItemNotFoundException) {
            //
        }

        return null;
    }
}