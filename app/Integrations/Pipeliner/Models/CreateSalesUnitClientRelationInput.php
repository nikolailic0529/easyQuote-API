<?php

namespace App\Integrations\Pipeliner\Models;

class CreateSalesUnitClientRelationInput extends BaseInput
{
    public function __construct(public readonly string $unitId)
    {
    }
}