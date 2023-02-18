<?php

namespace App\Domain\Worldwide\DataTransferObjects\QuoteStages;

use App\Domain\Worldwide\DataTransferObjects\DistributorQuote\DistributionMarginTaxCollection;
use App\Domain\Worldwide\Enum\ContractQuoteStage;
use Spatie\DataTransferObject\DataTransferObject;

final class ContractMarginTaxStage extends DataTransferObject
{
    public DistributionMarginTaxCollection $distributions_margin;

    public int $stage = ContractQuoteStage::MARGIN;
}
