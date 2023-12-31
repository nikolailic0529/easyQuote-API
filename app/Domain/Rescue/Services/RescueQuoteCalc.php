<?php

namespace App\Domain\Rescue\Services;

use App\Domain\Discount\DataTransferObjects\ImmutablePriceSummaryData;
use App\Domain\ExchangeRate\Contracts\ManagesExchangeRates;
use App\Domain\Rescue\Models\BaseQuote;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Rescue\Queries\QuoteQueries;
use App\Domain\Worldwide\DataTransferObjects\Calc\PriceSummaryData;

class RescueQuoteCalc
{
    public function __construct(protected QuoteQueries $quoteQueries,
                                protected ManagesExchangeRates $exchangeRateService)
    {
    }

    public function calculateListPriceOfRescueQuote(BaseQuote $version): float
    {
        if (!$version->use_groups) {
            return (float) $this->quoteQueries
                ->mappedSelectedRowsQuery($version)
                ->sum('price');
        }

        $groupedRowKeys = $version->groupedRows();

        $rowPriceMap = $this->quoteQueries
            ->mappedOrderedRowsQuery($version)
            ->whereIn('id', $groupedRowKeys)
            ->get()
            ->pluck('price', 'id');

        return collect($version->groupedRows())
            ->reduce(function (float $listPrice, string $id) use ($rowPriceMap) {
                return $listPrice + (float) $rowPriceMap->get($id);
            }, 0.0);
    }

    public function calculatePriceSummaryOfRescueQuote(Quote $quote): ImmutablePriceSummaryData
    {
        $activeVersion = $quote->activeVersionOrCurrent;

        $totalPrice = $this->calculateListPriceOfRescueQuote($activeVersion);

        $buyPrice = $activeVersion->buy_price;

        $countryMarginValue = (float) $quote->countryMargin?->value;
        $customDiscountValue = (float) $quote->custom_discount;

        $totalPriceAfterMargin = self::calculateTotalPriceAfterBottomUp(
            totalPrice: $totalPrice,
            buyPrice: (float) $quote->buy_price,
            marginDiffValue: $countryMarginValue
        );

        $totalPriceAfterCustomDiscount = self::calculateTotalPriceAfterBottomUp(
            totalPrice: $totalPrice,
            buyPrice: (float) $quote->buy_price,
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
            'source_to_output_exchange_rate' => $targetRate,
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
