<?php

namespace App\Services\WorldwideQuote\Calculation\Pipes;

use App\DTO\Discounts\ImmutableMultiYearDiscountData;
use App\DTO\Discounts\ImmutablePrePayDiscountData;
use App\DTO\Discounts\ImmutablePriceSummaryData;
use App\DTO\Discounts\ImmutablePromotionalDiscountData;
use App\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc;

final class ApplyPromotionalDiscountPipe
{
    public function __construct(protected ImmutablePromotionalDiscountData $discountData)
    {
    }

    public function handle(ImmutablePriceSummaryData $priceSummary, \Closure $next): mixed
    {
        if ($this->discountData->minimum_limit > $priceSummary->final_total_price) {
            $this->discountData->setApplicableValue(0.0);

            $priceSummary->setMarginAfterPromotionalDiscount(
                WorldwideQuoteCalc::calculateMarginPercentage($priceSummary->final_total_price, $priceSummary->buy_price)
            );

            return $next($priceSummary);
        }

        $finalTotalPriceBeforeDiscount = $priceSummary->final_total_price;

        $newFinalTotalPrice = WorldwideQuoteCalc::calculateTotalPriceAfterBottomUp(
            totalPrice: $priceSummary->final_total_price,
            buyPrice: $priceSummary->buy_price,
            marginDiffValue: -abs($this->discountData->value)
        );

        $this->discountData->setApplicableValue($finalTotalPriceBeforeDiscount - $newFinalTotalPrice);

        $priceSummary->setMarginAfterPromotionalDiscount(
            WorldwideQuoteCalc::calculateMarginPercentage($newFinalTotalPrice, $priceSummary->buy_price)
        );

        $priceSummary->setFinalTotalPrice($newFinalTotalPrice);

        return $next($priceSummary);
    }
}