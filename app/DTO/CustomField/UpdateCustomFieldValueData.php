<?php

namespace App\DTO\CustomField;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdateCustomFieldValueData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $entity_id;

    /**
     * @var string
     */
    public string $field_value;

    /**
     * @var bool
     */
    public bool $is_default;
}
