<?php

namespace App\Domain\UnifiedContract\DataTransferObjects\EntityFilter;

use Spatie\DataTransferObject\DataTransferObject;

final class FieldValue extends DataTransferObject
{
    public string $field_name;

    /**
     * @var bool|string|int|float
     */
    public $field_value;
}
