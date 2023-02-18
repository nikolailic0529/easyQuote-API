<?php

namespace App\Domain\Worldwide\DataTransferObjects\QuoteStages;

use App\Domain\Worldwide\Enum\PackQuoteStage;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class PackMarginTaxStage extends DataTransferObject
{
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

    public int $stage = PackQuoteStage::MARGIN;
}
