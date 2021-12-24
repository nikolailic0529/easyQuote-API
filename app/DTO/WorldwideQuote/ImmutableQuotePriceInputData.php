<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObject;
use Spatie\DataTransferObject\ImmutableDataTransferObject;

/**
 * Class ImmutableQuotePriceInputData
 *
 * @property-read float $total_price
 * @property-read float $buy_price
 * @property-read float $margin_value
 * @property-read float $tax_value
 */
final class ImmutableQuotePriceInputData extends ImmutableDataTransferObject
{
    public function __construct(QuotePriceInputData $dataTransferObject)
    {
        parent::__construct($dataTransferObject);
    }
}
