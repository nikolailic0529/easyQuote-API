<?php

namespace App\Domain\Worldwide\Services\WorldwideQuote\Calculation\Pipes;

use App\Domain\Discount\DataTransferObjects\ImmutablePriceSummaryData;
use App\Domain\Discount\DataTransferObjects\ImmutableSpecialNegotiationDiscountData;
use App\Domain\Worldwide\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc;

final class ApplySpecialNegotiationDiscountPipe
{
    public function __construct(protected ImmutableSpecialNegotiationDiscountData $discountData)
    {
    }

    public function handle(ImmutablePriceSummaryData $priceSummary, \Closure $next): mixed
    {
        $finalTotalPriceBeforeDiscount = $priceSummary->final_total_price;

        $newFinalTotalPrice = WorldwideQuoteCalc::calculateTotalPriceAfterBottomUp(
            totalPrice: $priceSummary->final_total_price,
            buyPrice: $priceSummary->buy_price,
            marginDiffValue: -abs($this->discountData->value)
        );

        $this->discountData->setApplicableValue($finalTotalPriceBeforeDiscount - $newFinalTotalPrice);

        $priceSummary->setMarginAfterSnDiscount(
            WorldwideQuoteCalc::calculateMarginPercentage($newFinalTotalPrice, $priceSummary->buy_price)
        );

        $priceSummary->setFinalTotalPrice($newFinalTotalPrice);

        return $next($priceSummary);
    }
}
