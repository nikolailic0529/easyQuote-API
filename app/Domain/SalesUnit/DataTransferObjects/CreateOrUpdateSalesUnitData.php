<?php

namespace App\Domain\SalesUnit\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class CreateOrUpdateSalesUnitData extends DataTransferObject
{
    #[Constraints\Uuid]
    public ?string $id;

    #[Constraints\NotBlank]
    public string $unit_name;

    public bool $is_default;

    public bool $is_enabled;

    #[Constraints\PositiveOrZero]
    public int $entity_order;
}
