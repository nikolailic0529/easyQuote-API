<?php

namespace App\Domain\Pipeliner\Services;

use App\Domain\Pipeliner\Integration\GraphQl\PipelinerSalesUnitIntegration;
use App\Domain\Pipeliner\Integration\Models\EntityFilterStringField;
use App\Domain\Pipeliner\Integration\Models\SalesUnitEntity;
use App\Domain\Pipeliner\Integration\Models\SalesUnitFilterInput;
use App\Domain\Pipeliner\Services\Exceptions\MultiplePipelinerEntitiesFoundException;
use App\Domain\SalesUnit\Models\SalesUnit;
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
        }

        return null;
    }
}
