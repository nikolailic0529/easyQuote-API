<?php

namespace App\Domain\Pipeliner\Integration\Models;

class CreateSalesUnitClientRelationInput extends BaseInput
{
    public function __construct(public readonly string $unitId)
    {
    }
}
