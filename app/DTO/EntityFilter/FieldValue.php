<?php

namespace App\DTO\EntityFilter;

use Spatie\DataTransferObject\DataTransferObject;

final class FieldValue extends DataTransferObject
{
    public string $field_name;

    /**
     * @var bool|string|int|float
     */
    public $field_value;
}