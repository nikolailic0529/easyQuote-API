<?php

namespace App\DTO\EntityFilter;

use Spatie\DataTransferObject\DataTransferObject;

final class TermValue extends DataTransferObject
{
    public string $term_name;

    /** @var string[]  */
    public array $term_values;
}