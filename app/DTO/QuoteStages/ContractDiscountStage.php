<?php

namespace App\DTO\QuoteStages;

use App\DTO\Discounts\DistributionDiscountsCollection;
use Spatie\DataTransferObject\DataTransferObject;
use App\Enum\ContractQuoteStage;

final class ContractDiscountStage extends DataTransferObject
{
    public DistributionDiscountsCollection $distributionDiscounts;

    public int $stage = ContractQuoteStage::DISCOUNT;
}
