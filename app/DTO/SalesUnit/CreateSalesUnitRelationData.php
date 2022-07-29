<?php

namespace App\DTO\SalesUnit;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class CreateSalesUnitRelationData extends DataTransferObject
{
    #[Constraints\Uuid]
    public string $id;
}