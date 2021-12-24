<?php

namespace App\Services\WorldwideQuote\Calculation\Pipes;

use App\DTO\Discounts\ImmutableMultiYearDiscountData;
use App\DTO\Discounts\ImmutablePriceSummaryData;
use App\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc;

final class ApplyMultiYearDiscountPipe
{
    public function __construct(protected ImmutableMultiYearDiscountData $discountData)
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

        $priceSummary->setMarginAfterMultiYearDiscount(
            WorldwideQuoteCalc::calculateMarginPercentage($newFinalTotalPrice, $priceSummary->buy_price)
        );

        $priceSummary->setFinalTotalPrice($newFinalTotalPrice);

        return $next($priceSummary);
    }
}