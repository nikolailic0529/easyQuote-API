<?php

namespace App\DTO\QuoteStages;

use App\DTO\DistributionMarginTaxCollection;
use App\Enum\ContractQuoteStage;
use Spatie\DataTransferObject\DataTransferObject;

final class ContractMarginTaxStage extends DataTransferObject
{
    public DistributionMarginTaxCollection $distributions_margin;

    public int $stage = ContractQuoteStage::MARGIN;
}
