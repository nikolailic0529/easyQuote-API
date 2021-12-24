<?php

namespace App\DTO\QuoteStages;

use App\DTO\DistributionMappingCollection;
use App\Enum\ContractQuoteStage;
use Spatie\DataTransferObject\DataTransferObject;

final class MappingStage extends DataTransferObject
{
    public DistributionMappingCollection $mapping;

    public int $stage = ContractQuoteStage::MAPPING;
}
