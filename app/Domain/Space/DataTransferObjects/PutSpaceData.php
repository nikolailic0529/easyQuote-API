<?php

namespace App\Domain\Space\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class PutSpaceData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public ?string $space_id;

    /**
     * @Constraints\NotBlank
     */
    public string $space_name;
}
