<?php

namespace App\Domain\Worldwide\DataTransferObjects\QuoteStages;

use App\Domain\Worldwide\DataTransferObjects\DistributorQuote\DistributionMappingCollection;
use App\Domain\Worldwide\Enum\ContractQuoteStage;
use Spatie\DataTransferObject\DataTransferObject;

final class MappingStage extends DataTransferObject
{
    public DistributionMappingCollection $mapping;

    public int $stage = ContractQuoteStage::MAPPING;
}
