<?php

namespace App\Domain\Margin\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;

final class MarginTaxData extends DataTransferObject
{
    public ?float $margin_value;

    public ?float $tax_value;

    public static function immutable(array $parameters = []): ImmutableMarginTaxData
    {
        return new ImmutableMarginTaxData(new MarginTaxData($parameters));
    }
}
