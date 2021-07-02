<?php

namespace App\Services\RescueQuote;

use App\Contracts\Services\ManagesExchangeRates;
use App\DTO\Discounts\ImmutablePriceSummaryData;
use App\DTO\PriceSummaryData;
use App\Models\Quote\Quote;
use App\Queries\QuoteQueries;

class RescueQuoteCalc
{
    public function __construct(protected QuoteQueries $quoteQueries, protected ManagesExchangeRates $exchangeRateService)
    {
    }

    public function calculatePriceSummaryOfRescueQuote(Quote $quote): ImmutablePriceSummaryData
    {
        $activeVersion = $quote->activeVersionOrCurrent;

        $totalPrice = (float)$this->quoteQueries
            ->mappedSelectedRowsQuery($activeVersion)
            ->sum('price');

        $buyPrice = $activeVersion->buy_price;

        $countryMarginValue = (float)$quote->countryMargin?->value;
        $customDiscountValue = (float)$quote->custom_discount;

        $totalPriceAfterMargin = self::calculateTotalPriceAfterBottomUp(
            totalPrice: $totalPrice,
            buyPrice: (float)$quote->buy_price,
            marginDiffValue: $countryMarginValue
        );

        $totalPriceAfterCustomDiscount = self::calculateTotalPriceAfterBottomUp(
            totalPrice: $totalPrice,
            buyPrice: (float)$quote->buy_price,
            marginDiffValue: $countryMarginValue - $customDiscountValue
        );

        $applicableDiscountsValue = $totalPriceAfterMargin - $totalPriceAfterCustomDiscount;

        $targetRate = value(function () use ($activeVersion): float {
            $targetRate = $this->exchangeRateService->getTargetRate($activeVersion->sourceCurrency, $activeVersion->targetCurrency);

            if (true === $activeVersion->targetCurrency->exists) {
                $targetRate = $targetRate + ($targetRate * $activeVersion->exchange_rate_margin / 100);
            }

            return $targetRate;
        });

        $finalTotalPrice = $totalPriceAfterMargin;

        return PriceSummaryData::immutable([
            'total_price' => $totalPrice * $targetRate,
            'total_price_after_margin' => $totalPriceAfterCustomDiscount * $targetRate,
            'buy_price' => $buyPrice * $targetRate,
            'final_total_price' => $totalPriceAfterCustomDiscount * $targetRate,
            'final_total_price_excluding_tax' => $totalPriceAfterCustomDiscount * $targetRate,
            'applicable_discounts_value' => $applicableDiscountsValue,
            'raw_margin' => self::calculateMarginPercentage($totalPrice, $buyPrice),
            'final_margin' => self::calculateMarginPercentage($finalTotalPrice, $buyPrice),
        ]);
    }

    final public static function calculateMarginPercentage(float $totalPrice, float $buyPrice): float
    {
        if ($totalPrice === 0.0) {
            return 0;
        }

        return (($totalPrice - $buyPrice) / $totalPrice) * 100;
    }

    final public static function calculateTotalPriceAfterBottomUp(float $totalPrice,
                                                                  float $buyPrice,
                                                                  float $marginDiffValue): float
    {
        if ($totalPrice === 0.0) {
            return 0.0;
        }

        $initialMarginPercentage = (($totalPrice - $buyPrice) / $totalPrice) * 100;

        $marginFloat = ($initialMarginPercentage + $marginDiffValue) / 100;

        $marginDivider = $marginFloat >= 1
            ? 1 / ($marginFloat + 1)
            : 1 - $marginFloat;

        return $buyPrice / $marginDivider;
    }
}