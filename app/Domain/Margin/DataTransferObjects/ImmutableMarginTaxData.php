<?php

namespace App\Domain\Margin\DataTransferObjects;

use Spatie\DataTransferObject\ImmutableDataTransferObject;

/**
 * Class ImmutableMarginTaxData.
 *
 * @property float|null $margin_value
 * @property float|null $tax_value
 */
final class ImmutableMarginTaxData extends ImmutableDataTransferObject
{
    public function __construct(MarginTaxData $dataTransferObject)
    {
        parent::__construct($dataTransferObject);
    }
}
