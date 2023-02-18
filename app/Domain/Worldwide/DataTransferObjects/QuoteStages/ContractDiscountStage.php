<?php

namespace App\Domain\Worldwide\DataTransferObjects\QuoteStages;

use App\Domain\Discount\DataTransferObjects\DistributionDiscountsCollection;
use App\Domain\Worldwide\Enum\ContractQuoteStage;
use Spatie\DataTransferObject\DataTransferObject;

final class ContractDiscountStage extends DataTransferObject
{
    public DistributionDiscountsCollection $distributionDiscounts;

    public int $stage = ContractQuoteStage::DISCOUNT;
}
