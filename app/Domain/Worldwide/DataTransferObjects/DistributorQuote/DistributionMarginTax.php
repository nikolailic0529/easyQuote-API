<?php

namespace App\Domain\Worldwide\DataTransferObjects\DistributorQuote;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

class DistributionMarginTax extends DataTransferObject
{
    /**
     * @Constraints\NotBlank()
     * @Constraints\Uuid()
     */
    public string $worldwide_distribution_id;

    public ?float $tax_value;

    public ?float $margin_value;

    /**
     * @Constraints\Choice({"New", "Renewal"})
     */
    public string $quote_type;

    /**
     * @Constraints\Choice({"No Margin", "Standard"})
     */
    public string $margin_method;
}
