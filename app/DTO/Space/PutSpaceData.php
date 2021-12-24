<?php

namespace App\DTO\Space;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class PutSpaceData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $space_id;

    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $space_name;
}
