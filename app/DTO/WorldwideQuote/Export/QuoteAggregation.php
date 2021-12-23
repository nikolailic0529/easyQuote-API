<?php

namespace App\DTO\WorldwideQuote\Export;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class QuoteAggregation extends DataTransferObject
{
    /**
     * @Constraints\NotBlank
     */
    public string $country_name;

    public string $country_code;

    /**
     * @Constraints\NotBlank
     */
    public string $vendor_name;

    /**
     * @Constraints\NotBlank
     */
    public string $duration;

    /**
     * @Constraints\PositiveOrZero()
     */
    public int $qty;

    /**
     * @Constraints\NotBlank
     */
    public string $total_price;
}
