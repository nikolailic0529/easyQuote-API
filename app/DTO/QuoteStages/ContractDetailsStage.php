<?php

namespace App\DTO\QuoteStages;

use App\DTO\DistributionDetailsCollection;
use App\Enum\ContractQuoteStage;
use Spatie\DataTransferObject\DataTransferObject;

final class ContractDetailsStage extends DataTransferObject
{
    public DistributionDetailsCollection $distributionDetailsCollection;

    public int $stage = ContractQuoteStage::DETAIL;
}
