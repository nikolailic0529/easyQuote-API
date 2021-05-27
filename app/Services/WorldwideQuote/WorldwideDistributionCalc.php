<?php

namespace App\Services\WorldwideQuote;

use App\DTO\{Discounts\ApplicablePredefinedDiscounts,
    Discounts\ImmutableCustomDiscountData,
    Discounts\ImmutableMultiYearDiscountData,
    Discounts\ImmutablePrePayDiscountData,
    Discounts\ImmutablePriceSummaryData,
    Discounts\ImmutablePromotionalDiscountData,
    Discounts\ImmutableSpecialNegotiationDiscountData,
    Discounts\MultiYearDiscountData,
    Discounts\PrePayDiscountData,
    Discounts\PromotionalDiscountData,
    Discounts\SpecialNegotiationDiscountData,
    Margin\ImmutableMarginTaxData,
    PriceSummaryData,
    WorldwideQuote\DistributionFinalTotalPrice};
use App\Models\{Quote\Discount\MultiYearDiscount,
    Quote\Discount\PrePayDiscount,
    Quote\Discount\PromotionalDiscount,
    Quote\Discount\SND,
    Quote\WorldwideDistribution};
use App\Queries\WorldwideDistributionQueries;
use App\Services\ExchangeRate\CurrencyConverter;
use Illuminate\Pipeline\Pipeline;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function data_get;
use function transform;
use function with;

class WorldwideDistributionCalc
{
    protected ValidatorInterface $validator;
    protected CurrencyConverter $currencyConverter;
    protected WorldwideDistributionQueries $queries;
    protected Pipeline $pipeline;

    public function __construct(ValidatorInterface $validator,
                                CurrencyConverter $currencyConverter,
                                WorldwideDistributionQueries $queries,
                                Pipeline $pipeline)
    {
        $this->validator = $validator;
        $this->currencyConverter = $currencyConverter;
        $this->queries = $queries;
        $this->pipeline = $pipeline;
    }

    public static function multiYearDiscountToImmutableMultiYearDiscountData(MultiYearDiscount $discount): ImmutableMultiYearDiscountData
    {
        return new ImmutableMultiYearDiscountData(
            new MultiYearDiscountData(['value' => (float)data_get($discount->durations, 'duration.value')])
        );
    }

    public static function prePayDiscountToImmutablePrePayDiscountData(PrePayDiscount $discount): ImmutablePrePayDiscountData
    {
        return new ImmutablePrePayDiscountData(
            new PrePayDiscountData(['value' => (float)data_get($discount->durations, 'duration.value')])
        );
    }

    public static function promotionalDiscountToImmutablePromotionalDiscountData(PromotionalDiscount $discount): ImmutablePromotionalDiscountData
    {
        return new ImmutablePromotionalDiscountData(
            new PromotionalDiscountData(['value' => (float)$discount->value, 'minimum_limit' => (float)$discount->minimum_limit])
        );
    }

    public static function snDiscountToImmutableSpecialNegotiationData(SND $discount): ImmutableSpecialNegotiationDiscountData
    {
        return new ImmutableSpecialNegotiationDiscountData(
            new SpecialNegotiationDiscountData(['value' => (float)$discount->value])
        );
    }

    public function calculateDistributionFinalTotalPrice(WorldwideDistribution $distribution, float $distributionTotalPrice): DistributionFinalTotalPrice
    {
        $buyPrice = $this->calculateBuyPriceOfDistributorQuote($distribution);

        $totalPriceAfterMargin = $this->calculateTotalPriceAfterBottomUp($distributionTotalPrice, $buyPrice, (float)$distribution->margin_value, (float)$distribution->custom_discount);

        $totalPriceAfterTax = $this->calculateTotalPriceAfterTax($totalPriceAfterMargin, (float)$distribution->tax_value);

        $applicableDiscounts = $this->predefinedDistributionDiscountsToApplicableDiscounts($distribution);

        $finalTotalPriceValue = (float)$this->calculateTotalPriceAfterPredefinedDiscounts($totalPriceAfterTax, $applicableDiscounts);

        $applicableDiscountsValue = with($distribution->custom_discount, function (?float $customDiscountValue) use ($distributionTotalPrice, $finalTotalPriceValue, $applicableDiscounts) {
            if (!is_null($customDiscountValue)) {
                return $distributionTotalPrice - $finalTotalPriceValue;
            }

            return $this->calculateApplicableDiscountsValue($applicableDiscounts);
        });

        return new DistributionFinalTotalPrice([
            'final_total_price_value' => $finalTotalPriceValue,
            'applicable_discounts_value' => $applicableDiscountsValue,
        ]);
    }

    public function calculateTotalPriceAfterBottomUp(float $totalPrice, float $buyPrice, float $countryMarginValue, float $customDiscountValue): float
    {
        if ($totalPrice === 0.0) {
            return 0.0;
        }

        $initialMarginPercentage = (($totalPrice - $buyPrice) / $totalPrice) * 100;

        $marginFloat = ($initialMarginPercentage + ($countryMarginValue - $customDiscountValue)) / 100;

        $marginDivider = $marginFloat >= 1
            ? 1 / ($marginFloat + 1)
            : 1 - $marginFloat;

        return $buyPrice / $marginDivider;
    }

    public function calculateTotalPriceAfterTax(float $totalPrice, float $taxValue): float
    {
        return $totalPrice + $taxValue;
    }

    public function predefinedDistributionDiscountsToApplicableDiscounts(WorldwideDistribution $distribution): ApplicablePredefinedDiscounts
    {
        return new ApplicablePredefinedDiscounts([
            'multi_year_discount' => transform($distribution->multiYearDiscount, [static::class, 'multiYearDiscountToImmutableMultiYearDiscountData']),
            'pre_pay_discount' => transform($distribution->prePayDiscount, [static::class, 'prePayDiscountToImmutablePrePayDiscountData']),
            'promotional_discount' => transform($distribution->promotionalDiscount, [static::class, 'promotionalDiscountToImmutablePromotionalDiscountData']),
            'special_negotiation_discount' => transform($distribution->snDiscount, [static::class, 'snDiscountToImmutableSpecialNegotiationData']),
        ]);
    }

    public function calculateTotalPriceAfterPredefinedDiscounts(float $totalPrice, ApplicablePredefinedDiscounts $applicableDiscounts): float
    {
        $pipes = [];

        if (!is_null($applicableDiscounts->multi_year_discount)) {
            $pipes[] = $this->calculatePriceAfterMultiYearDiscountPipe(
                $applicableDiscounts->multi_year_discount,
            );
        }

        if (!is_null($applicableDiscounts->pre_pay_discount)) {
            $pipes[] = $this->calculatePriceAfterPrePayDiscountPipe(
                $applicableDiscounts->pre_pay_discount,
            );
        }

        if (!is_null($applicableDiscounts->promotional_discount)) {
            $pipes[] = $this->calculatePriceAfterPromotionalDiscountPipe(
                $applicableDiscounts->promotional_discount,
            );
        }

        if (!is_null($applicableDiscounts->special_negotiation_discount)) {
            $pipes[] = $this->calculatePriceAfterSpecialNegotiationDiscountPipe(
                $applicableDiscounts->special_negotiation_discount,
            );
        }

        return (float)$this->pipeline
            ->send($totalPrice)
            ->through($pipes)
            ->thenReturn();
    }

    private function calculatePriceAfterMultiYearDiscountPipe(ImmutableMultiYearDiscountData $discountData): \Closure
    {
        return function (float $totalPrice, \Closure $next) use ($discountData) {
            $discountPercentage = $discountData->value / 100;

            $discountData->setApplicableValue($totalPrice * $discountPercentage);

            $totalPrice = $totalPrice - $discountData->applicableValue;

            return $next($totalPrice);
        };
    }

    private function calculatePriceAfterPrePayDiscountPipe(ImmutablePrePayDiscountData $discountData): \Closure
    {
        return function (float $totalPrice, \Closure $next) use ($discountData) {
            $discountPercentage = $discountData->value / 100;

            $discountData->setApplicableValue($totalPrice * $discountPercentage);

            $totalPrice = $totalPrice - $discountData->applicableValue;

            return $next($totalPrice);
        };
    }

    private function calculatePriceAfterPromotionalDiscountPipe(ImmutablePromotionalDiscountData $discountData): \Closure
    {
        return function (float $totalPrice, \Closure $next) use ($discountData) {
            if ($discountData->minimum_limit > $totalPrice) {
                return $next($totalPrice);
            }

            $discountPercentage = $discountData->value / 100;

            $discountData->setApplicableValue($totalPrice * $discountPercentage);

            $totalPrice = $totalPrice - $discountData->applicableValue;

            return $next($totalPrice);
        };
    }

    private function calculatePriceAfterSpecialNegotiationDiscountPipe(ImmutableSpecialNegotiationDiscountData $discountData): \Closure
    {
        return function (float $totalPrice, \Closure $next) use ($discountData) {
            $discountPercentage = $discountData->value / 100;

            $discountData->setApplicableValue($totalPrice * $discountPercentage);

            $totalPrice = $totalPrice - $discountData->applicableValue;

            return $next($totalPrice);
        };
    }

    protected function calculateApplicableDiscountsValue(ApplicablePredefinedDiscounts $applicableDiscounts): float
    {
        return array_sum([
            (float)transform($applicableDiscounts->multi_year_discount, fn(ImmutableMultiYearDiscountData $discountData) => $discountData->applicableValue),
            (float)transform($applicableDiscounts->pre_pay_discount, fn(ImmutablePrePayDiscountData $discountData) => $discountData->applicableValue),
            (float)transform($applicableDiscounts->promotional_discount, fn(ImmutablePromotionalDiscountData $discountData) => $discountData->applicableValue),
            (float)transform($applicableDiscounts->special_negotiation_discount, fn(ImmutableSpecialNegotiationDiscountData $discountData) => $discountData->applicableValue),
        ]);
    }

    public function calculateBuyPriceOfDistributorQuote(WorldwideDistribution $distribution): float
    {
        if (is_null($distribution->distributionCurrency) || is_null($distribution->buyCurrency)) {
            return (float)$distribution->buy_price;
        }

        return $this->currencyConverter->convertCurrencies(
            $distribution->buyCurrency->code,
            $distribution->distributionCurrency->code,
            (float)$distribution->buy_price
        );
    }

    public function calculatePriceSummaryOfDistributorQuote(WorldwideDistribution $distribution): ImmutablePriceSummaryData
    {
        $quoteTotalPrice = $this->calculateDistributionTotalPrice($distribution);

        $quoteBuyPrice = $this->calculateBuyPriceOfDistributorQuote($distribution);

        $rawMarginPercentage = $this->calculateMarginPercentage($quoteTotalPrice, $quoteBuyPrice);

        $totalPriceAfterMargin = $this->calculateTotalPriceAfterBottomUp($quoteTotalPrice, $quoteBuyPrice, (float)$distribution->margin_value, (float)$distribution->custom_discount);

        $totalPriceAfterMarginWithoutCustomDiscount = $this->calculateTotalPriceAfterBottomUp($quoteTotalPrice, $quoteBuyPrice, (float)$distribution->margin_value, 0.0);

        $marginPercentageAfterCustomDiscount = $this->calculateMarginPercentage($totalPriceAfterMargin, $quoteBuyPrice);

        $applicableDiscounts = $this->predefinedDistributionDiscountsToApplicableDiscounts($distribution);

        $totalPriceAfterDiscounts = $this->calculateTotalPriceAfterPredefinedDiscounts($totalPriceAfterMargin, $applicableDiscounts);

        $applicableDiscountsValue = with($distribution->custom_discount, function (?float $customDiscountValue) use ($totalPriceAfterMargin, $totalPriceAfterMarginWithoutCustomDiscount, $quoteTotalPrice, $totalPriceAfterDiscounts, $applicableDiscounts) {
            if (!is_null($customDiscountValue)) {
                return $totalPriceAfterMarginWithoutCustomDiscount - $totalPriceAfterMargin;
            }

            return $this->calculateApplicableDiscountsValue($applicableDiscounts);
        });

        $totalPriceAfterTax = $this->calculateTotalPriceAfterTax($totalPriceAfterDiscounts, (float)$distribution->tax_value);

        $finalMarginPercentage = $this->calculateMarginPercentage($totalPriceAfterDiscounts, $quoteBuyPrice);

        return PriceSummaryData::immutable([
            'total_price' => $quoteTotalPrice,
            'total_price_after_margin' => $totalPriceAfterMarginWithoutCustomDiscount,
            'buy_price' => $this->calculateBuyPriceOfDistributorQuote($distribution),
            'final_total_price' => $totalPriceAfterTax,
            'final_total_price_excluding_tax' => $totalPriceAfterDiscounts,
            'applicable_discounts_value' => $applicableDiscountsValue,
            'raw_margin' => $rawMarginPercentage,
            'final_margin' => $finalMarginPercentage,
            'margin_after_custom_discount' => $marginPercentageAfterCustomDiscount
        ]);
    }

    public function calculateDistributionTotalPrice(WorldwideDistribution $distribution): float
    {
        return (float)$this->queries->distributionTotalPriceQuery($distribution)->value('total_price');
    }

    public function calculateMarginPercentage(float $totalPrice, float $buyPrice): float
    {
        if ($totalPrice === 0.0) {
            return 0;
        }

        return (($totalPrice - $buyPrice) / $totalPrice) * 100;
    }

    public function calculatePriceSummaryAfterMarginTax(WorldwideDistribution $distribution, ImmutableMarginTaxData $marginTaxData): ImmutablePriceSummaryData
    {
        $violations = $this->validator->validate($marginTaxData);

        if (count($violations)) {
            throw new ValidationFailedException($marginTaxData, $violations);
        }

        $distributionBuyPrice = $this->calculateBuyPriceOfDistributorQuote($distribution);

        $distributionTotalPrice = (float)$this->queries->distributionTotalPriceQuery($distribution)->value('total_price');

        $totalPriceAfterMargin = $this->calculateTotalPriceAfterBottomUp($distributionTotalPrice, $distributionBuyPrice, (float)$marginTaxData->margin_value, (float)$distribution->custom_discount);

        $totalPriceAfterMarginWithoutCustomDiscount = $this->calculateTotalPriceAfterBottomUp($distributionTotalPrice, $distributionBuyPrice, (float)$marginTaxData->margin_value, 0.0);

        $predefinedDiscounts = $this->predefinedDistributionDiscountsToApplicableDiscounts($distribution);

        $totalPriceAfterPredefinedDiscounts = $this->calculateTotalPriceAfterPredefinedDiscounts($totalPriceAfterMargin, $predefinedDiscounts);

        $finalTotalPrice = $this->calculateTotalPriceAfterTax($totalPriceAfterPredefinedDiscounts, (float)$marginTaxData->tax_value);

        $marginValueAfterTax = $this->calculateMarginPercentage($totalPriceAfterPredefinedDiscounts, $distributionBuyPrice);

        return new ImmutablePriceSummaryData(new PriceSummaryData([
            'total_price' => $distributionTotalPrice,
            'total_price_after_margin' => $totalPriceAfterMarginWithoutCustomDiscount,
            'final_total_price' => $finalTotalPrice,
            'final_total_price_excluding_tax' => $totalPriceAfterPredefinedDiscounts,
            'buy_price' => $distributionBuyPrice,
            'final_margin' => $marginValueAfterTax,
            'applicable_discounts_value' => null,
        ]));
    }

    public function calculatePriceSummaryAfterCustomDiscount(WorldwideDistribution $distribution, ImmutableCustomDiscountData $discountData): ImmutablePriceSummaryData
    {
        $violations = $this->validator->validate($discountData);

        if (count($violations)) {
            throw new ValidationFailedException($discountData, $violations);
        }

        $distributionTotalPrice = (float)$this->queries->distributionTotalPriceQuery($distribution)->value('total_price');

        $distributionBuyPrice = $this->calculateBuyPriceOfDistributorQuote($distribution);

        $rawMarginPercentage = $this->calculateMarginPercentage($distributionTotalPrice, $distributionBuyPrice);

        $totalPriceAfterMargin = $this->calculateTotalPriceAfterBottomUp($distributionTotalPrice, $distributionBuyPrice, (float)$distribution->margin_value, 0.0);

//        $totalPriceAfterTax = $this->calculateTotalPriceAfterTax($totalPriceAfterMargin, (float)$distribution->tax_value);

        $totalPriceAfterCustomDiscount = $this->calculateTotalPriceAfterBottomUp($distributionTotalPrice, $distributionBuyPrice, (float)$distribution->margin_value, $discountData->value);

        $marginValueAfterCustomDiscount = $this->calculateMarginPercentage($totalPriceAfterCustomDiscount, $distributionBuyPrice);

        $totalPriceAfterCustomDiscountAndTax = $this->calculateTotalPriceAfterTax($totalPriceAfterCustomDiscount, (float)$distribution->tax_value);

        return new ImmutablePriceSummaryData(new PriceSummaryData([
            'total_price' => $distributionTotalPrice,
            'total_price_after_margin' => $totalPriceAfterMargin,
            'final_total_price' => $totalPriceAfterCustomDiscountAndTax,
            'final_total_price_excluding_tax' => $totalPriceAfterCustomDiscount,
            'buy_price' => $distributionBuyPrice,
            'margin_after_custom_discount' => $marginValueAfterCustomDiscount,
            'applicable_discounts_value' => $totalPriceAfterMargin - $totalPriceAfterCustomDiscount,
        ]));
    }

    public function calculatePriceSummaryAfterPredefinedDiscounts(WorldwideDistribution $distribution, ApplicablePredefinedDiscounts $applicableDiscounts): ImmutablePriceSummaryData
    {
        $violations = $this->validator->validate($applicableDiscounts);

        if (count($violations)) {
            throw new ValidationFailedException($applicableDiscounts, $violations);
        }

        $distributionBuyPrice = $this->calculateBuyPriceOfDistributorQuote($distribution);

        $distributionTotalPrice = (float)$this->queries->distributionTotalPriceQuery($distribution)->value('total_price');

        $totalPriceAfterMargin = $this->calculateTotalPriceAfterBottomUp($distributionTotalPrice, $distributionBuyPrice, (float)$distribution->margin_value, 0);

        $priceSummary = new ImmutablePriceSummaryData(new PriceSummaryData([
            'total_price' => $distributionTotalPrice,
            'total_price_after_margin' => $totalPriceAfterMargin,
            'final_total_price' => $totalPriceAfterMargin,
            'buy_price' => $distributionBuyPrice,
        ]));

        $pipes = [];

        if (!is_null($applicableDiscounts->multi_year_discount)) {
            $pipes[] = $this->calculateMarginAfterMultiYearDiscountPipe($applicableDiscounts->multi_year_discount);
        }

        if (!is_null($applicableDiscounts->pre_pay_discount)) {
            $pipes[] = $this->calculateMarginAfterPrePayDiscountPipe($applicableDiscounts->pre_pay_discount);
        }

        if (!is_null($applicableDiscounts->promotional_discount)) {
            $pipes[] = $this->calculateMarginAfterPromotionalDiscountPipe($applicableDiscounts->promotional_discount);
        }

        if (!is_null($applicableDiscounts->special_negotiation_discount)) {
            $pipes[] = $this->calculateMarginAfterSpecialNegotiationDiscountPipe($applicableDiscounts->special_negotiation_discount);
        }

        $this->pipeline
            ->send($priceSummary)
            ->through($pipes)
            ->thenReturn();

        $priceSummary->setApplicableDiscountsValue(
            $totalPriceAfterMargin - $priceSummary->final_total_price
        );

        $priceSummary->setFinalTotalPriceExcludingTax($priceSummary->final_total_price);

        $priceSummary->setFinalTotalPrice(
            $this->calculateTotalPriceAfterTax($priceSummary->final_total_price, (float)$distribution->tax_value)
        );

        return $priceSummary;
    }

    private function calculateMarginAfterMultiYearDiscountPipe(ImmutableMultiYearDiscountData $discountData): \Closure
    {
        return function (ImmutablePriceSummaryData $marginData, \Closure $next) use ($discountData) {
            $discountPercentage = $discountData->value / 100;

            $discountData->setApplicableValue($marginData->final_total_price * $discountPercentage);

            $totalPrice = $marginData->final_total_price - $discountData->applicableValue;

            $marginData->setMarginAfterMultiYearDiscount(
                $this->calculateMarginPercentage($totalPrice, $marginData->buy_price)
            );

            $marginData->setFinalTotalPrice($totalPrice);

            return $next($marginData);
        };
    }

    private function calculateMarginAfterPrePayDiscountPipe(ImmutablePrePayDiscountData $discountData): \Closure
    {
        return function (ImmutablePriceSummaryData $marginData, \Closure $next) use ($discountData) {
            $discountPercentage = $discountData->value / 100;

            $discountData->setApplicableValue($marginData->final_total_price * $discountPercentage);

            $totalPrice = $marginData->final_total_price - $discountData->applicableValue;

            $marginData->setMarginAfterPrePayDiscount(
                $this->calculateMarginPercentage($totalPrice, $marginData->buy_price)
            );

            $marginData->setFinalTotalPrice($totalPrice);

            return $next($marginData);
        };
    }

    private function calculateMarginAfterPromotionalDiscountPipe(ImmutablePromotionalDiscountData $discountData): \Closure
    {
        return function (ImmutablePriceSummaryData $marginData, \Closure $next) use ($discountData) {
            if ($discountData->minimum_limit > $marginData->final_total_price) {
                $discountData->setApplicableValue(0.0);

                $marginData->setMarginAfterPromotionalDiscount(
                    $this->calculateMarginPercentage($marginData->final_total_price, $marginData->buy_price)
                );

                return $next($marginData);
            }

            $discountPercentage = $discountData->value / 100;

            $discountData->setApplicableValue($marginData->final_total_price * $discountPercentage);

            $totalPrice = $marginData->final_total_price - $discountData->applicableValue;

            $marginData->setMarginAfterPromotionalDiscount(
                $this->calculateMarginPercentage($totalPrice, $marginData->buy_price)
            );

            $marginData->setFinalTotalPrice($totalPrice);

            return $next($marginData);
        };
    }

    private function calculateMarginAfterSpecialNegotiationDiscountPipe(ImmutableSpecialNegotiationDiscountData $discountData): \Closure
    {
        return function (ImmutablePriceSummaryData $marginData, \Closure $next) use ($discountData) {
            $discountPercentage = $discountData->value / 100;

            $discountData->setApplicableValue($marginData->final_total_price * $discountPercentage);

            $totalPrice = $marginData->final_total_price - $discountData->applicableValue;

            $marginData->setMarginAfterSnDiscount(
                $this->calculateMarginPercentage($totalPrice, $marginData->buy_price)
            );

            $marginData->setFinalTotalPrice($totalPrice);

            return $next($marginData);
        };
    }

    public function calculateMarginAfterDiscountValue(float $totalPrice, float $buyPrice, float $discountValue): float
    {
        $totalPriceAfterDiscount = $this->calculateTotalPriceAfterCustomDiscount($totalPrice, $discountValue);

        return static::calculateMargin($totalPriceAfterDiscount, $buyPrice);
    }

    public function calculateTotalPriceAfterCustomDiscount(float $totalPrice, float $customDiscount): float
    {
        $customDiscountValue = $customDiscount / 100;

        return $totalPrice - $totalPrice * $customDiscountValue;
    }

    public static function calculateMargin(float $totalPrice, float $buyPrice): float
    {
        if ($totalPrice === 0.0) {
            return 0;
        }

        return (($totalPrice - $buyPrice) / $totalPrice) * 100;
    }
}
