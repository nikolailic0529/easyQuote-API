<?php

namespace App\Services\WorldwideQuote\Calculation\Pipes;

use App\DTO\Discounts\ImmutableMultiYearDiscountData;
use App\DTO\Discounts\ImmutablePrePayDiscountData;
use App\DTO\Discounts\ImmutablePriceSummaryData;
use App\DTO\Discounts\ImmutableSpecialNegotiationDiscountData;
use App\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc;

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