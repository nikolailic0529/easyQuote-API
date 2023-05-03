<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote\Export;

use Spatie\DataTransferObject\DataTransferObject;

final class AggregationField extends DataTransferObject
{
    /**
     * @Constraints\Choice({"vendor_name", "country_name", "duration", "quantity", "total_price"})
     */
    public string $field_name;

    /**
     * @Constraints\NotBlank
     */
    public string $field_header;
}
