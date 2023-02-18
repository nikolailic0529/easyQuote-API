<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\ImmutableDataTransferObject;

/**
 * Class ImmutableQuotePriceInputData.
 *
 * @property float $total_price
 * @property float $buy_price
 * @property float $margin_value
 * @property float $tax_value
 */
final class ImmutableQuotePriceInputData extends ImmutableDataTransferObject
{
    public function __construct(QuotePriceInputData $dataTransferObject)
    {
        parent::__construct($dataTransferObject);
    }
}
