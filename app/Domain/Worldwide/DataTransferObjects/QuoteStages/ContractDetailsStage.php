<?php

namespace App\Domain\Worldwide\DataTransferObjects\QuoteStages;

use App\Domain\Worldwide\DataTransferObjects\DistributorQuote\DistributionDetailsCollection;
use App\Domain\Worldwide\Enum\ContractQuoteStage;
use Spatie\DataTransferObject\DataTransferObject;

final class ContractDetailsStage extends DataTransferObject
{
    public DistributionDetailsCollection $distributionDetailsCollection;

    public int $stage = ContractQuoteStage::DETAIL;
}
