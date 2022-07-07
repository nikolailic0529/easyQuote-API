<?php

namespace App\DTO\CustomField;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdateCustomFieldValueData extends DataTransferObject
{
    #[Constraints\Uuid]
    public ?string $entity_id;

    #[Constraints\NotBlank]
    public string $field_value;

    public bool $is_default;

    #[Constraints\All(new Constraints\Uuid)]
    public array $allowed_by = [];
}
