<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Enum\InputValueEnum;

class CreateClientInput extends BaseInput
{
    public function __construct(public readonly string $email,
                                public readonly string $masterRightId,
                                public readonly string $defaultUnitId,
                                public readonly CreateSalesUnitClientRelationInputCollection $unitMembership,
                                public readonly string|InputValueEnum $firstName = InputValueEnum::Miss,
                                public readonly string|InputValueEnum $middleName = InputValueEnum::Miss,
                                public readonly string|InputValueEnum $lastName = InputValueEnum::Miss)
    {
    }
}