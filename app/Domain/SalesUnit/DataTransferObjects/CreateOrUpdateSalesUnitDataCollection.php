<?php

namespace App\Domain\SalesUnit\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class CreateOrUpdateSalesUnitDataCollection extends DataTransferObjectCollection
{
    public function current(): CreateOrUpdateSalesUnitData
    {
        return parent::current();
    }
}
