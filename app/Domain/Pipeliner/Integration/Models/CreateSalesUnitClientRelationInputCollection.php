<?php

namespace App\Domain\Pipeliner\Integration\Models;

class CreateSalesUnitClientRelationInputCollection extends BaseInputCollection
{
    public function current(): CreateSalesUnitClientRelationInput
    {
        return parent::current();
    }
}
