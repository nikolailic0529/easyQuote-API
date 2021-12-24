<?php

namespace App\DTO\Margin;

use Spatie\DataTransferObject\DataTransferObject;
use Spatie\DataTransferObject\ImmutableDataTransferObject;

final class MarginTaxData extends DataTransferObject
{
    public ?float $margin_value;

    public ?float $tax_value;

    public static function immutable(array $parameters = []): ImmutableMarginTaxData
    {
        return new ImmutableMarginTaxData(new MarginTaxData($parameters));
    }
}
