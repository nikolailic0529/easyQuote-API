<?php

namespace App\Domain\Worldwide\Services\WorldwideQuote\Calculation;

use App\Domain\Discount\DataTransferObjects\ApplicablePredefinedDiscounts;
use App\Domain\Discount\DataTransferObjects\ImmutableCustomDiscountData;
use App\Domain\Discount\DataTransferObjects\ImmutableMultiYearDiscountData;
use App\Domain\Discount\DataTransferObjects\ImmutablePrePayDiscountData;
use App\Domain\Discount\DataTransferObjects\ImmutablePriceSummaryData;
use App\Domain\Discount\DataTransferObjects\ImmutablePromotionalDiscountData;
use App\Domain\Discount\DataTransferObjects\ImmutableSpecialNegotiationDiscountData;
use App\Domain\ExchangeRate\Services\CurrencyConverter;
use App\Domain\Margin\DataTransferObjects\ImmutableMarginTaxData;
use App\Domain\Worldwide\DataTransferObjects\Calc\{PriceSummaryData};
use App\Domain\Worldwide\DataTransferObjects\Quote\DistributionFinalTotalPrice;
use App\Domain\Worldwide\DataTransferObjects\Quote\ImmutableQuotePriceInputData;
use App\Domain\Worldwide\DataTransferObjects\Quote\QuotePriceInputData;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Queries\WorldwideDistributionQueries;
use Carbon\Carbon;
use Illuminate\Pipeline\Pipeline;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WorldwideDistributorQuoteCalc
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

    public function calculateDistributionFinalTotalPrice(WorldwideDistribution $distribution, float $distributionTotalPrice): DistributionFinalTotalPrice
    {
        $buyPrice = $this->calculateBuyPriceOfDistributorQuote($distribution);

        $predefinedDiscounts = $this->predefinedDistributionDiscountsToApplicableDiscounts($distribution);

        $priceSummaryAfterPredefinedDiscounts = $this->calculatePriceSummaryAfterPredefinedDiscounts(
            QuotePriceInputData::immutable([
                'total_price' => $distributionTotalPrice,
                'buy_price' => $buyPrice,
                'margin_value' => (float) $distribution->margin_value - (float) $distribution->custom_discount,
                'tax_value' => (float) $distribution->tax_value,
            ]),
            $predefinedDiscounts
        );

        $finalTotalPriceValue = $priceSummaryAfterPredefinedDiscounts->final_total_price_excluding_tax;

        $applicableDiscountsValue = \with($distribution->custom_discount, function (?float $customDiscountValue) use ($distributionTotalPrice, $finalTotalPriceValue, $predefinedDiscounts) {
            if (!is_null($customDiscountValue)) {
                return $distributionTotalPrice - $finalTotalPriceValue;
            }

            return $this->calculateApplicableDiscountsValue($predefinedDiscounts);
        });

        return new DistributionFinalTotalPrice([
            'final_total_price_value' => $finalTotalPriceValue,
            'applicable_discounts_value' => $applicableDiscountsValue,
        ]);
    }

    public function predefinedDistributionDiscountsToApplicableDiscounts(WorldwideDistribution $distribution): ApplicablePredefinedDiscounts
    {
        return new ApplicablePredefinedDiscounts([
            'multi_year_discount' => \transform($distribution->multiYearDiscount, [WorldwideQuoteCalc::class, 'multiYearDiscountToImmutableMultiYearDiscountData']),
            'pre_pay_discount' => \transform($distribution->prePayDiscount, [WorldwideQuoteCalc::class, 'prePayDiscountToImmutablePrePayDiscountData']),
            'promotional_discount' => \transform($distribution->promotionalDiscount, [WorldwideQuoteCalc::class, 'promotionalDiscountToImmutablePromotionalDiscountData']),
            'special_negotiation_discount' => \transform($distribution->snDiscount, [WorldwideQuoteCalc::class, 'snDiscountToImmutableSpecialNegotiationData']),
        ]);
    }

    protected function calculateApplicableDiscountsValue(ApplicablePredefinedDiscounts $applicableDiscounts): float
    {
        return array_sum([
            (float) \transform($applicableDiscounts->multi_year_discount, fn (ImmutableMultiYearDiscountData $discountData) => $discountData->applicableValue),
            (float) \transform($applicableDiscounts->pre_pay_discount, fn (ImmutablePrePayDiscountData $discountData) => $discountData->applicableValue),
            (float) \transform($applicableDiscounts->promotional_discount, fn (ImmutablePromotionalDiscountData $discountData) => $discountData->applicableValue),
            (float) \transform($applicableDiscounts->special_negotiation_discount, fn (ImmutableSpecialNegotiationDiscountData $discountData) => $discountData->applicableValue),
        ]);
    }

    public function calculateBuyPriceOfDistributorQuote(WorldwideDistribution $distribution): float
    {
        if (is_null($distribution->worldwideQuote->quoteCurrency) || is_null($distribution->buyCurrency)) {
            return (float) $distribution->buy_price;
        }

        $distributionCreationDate = \transform($distribution->{$distribution->getCreatedAtColumn()}, fn ($date) => Carbon::instance($date));

        return $this->currencyConverter->convertCurrencies(
            $distribution->buyCurrency->code,
            $distribution->worldwideQuote->quoteCurrency->code,
            (float) $distribution->buy_price,
            $distributionCreationDate
        );
    }

    public function calculatePriceSummaryOfDistributorQuote(WorldwideDistribution $distribution): ImmutablePriceSummaryData
    {
        $quoteTotalPrice = $this->calculateTotalPriceOfDistributorQuote($distribution);

        $quoteBuyPrice = $this->calculateBuyPriceOfDistributorQuote($distribution);

        $rawMarginPercentage = WorldwideQuoteCalc::calculateMarginPercentage($quoteTotalPrice, $quoteBuyPrice);

        $totalPriceAfterMargin = WorldwideQuoteCalc::calculateTotalPriceAfterBottomUp($quoteTotalPrice, $quoteBuyPrice, (float) $distribution->margin_value - (float) $distribution->custom_discount);

        $totalPriceAfterMarginWithoutCustomDiscount = WorldwideQuoteCalc::calculateTotalPriceAfterBottomUp($quoteTotalPrice, $quoteBuyPrice, (float) $distribution->margin_value);

        $marginPercentageAfterCustomDiscount = WorldwideQuoteCalc::calculateMarginPercentage($totalPriceAfterMargin, $quoteBuyPrice);

        $applicableDiscounts = $this->predefinedDistributionDiscountsToApplicableDiscounts($distribution);

        $priceSummaryAfterDiscounts = $this->calculatePriceSummaryAfterPredefinedDiscounts(
            QuotePriceInputData::immutable([
                'total_price' => $quoteTotalPrice,
                'buy_price' => $quoteBuyPrice,
                'margin_value' => (float) $distribution->margin_value - (float) $distribution->custom_discount,
                'tax_value' => (float) $distribution->tax_value,
            ]),
            $applicableDiscounts
        );

        $totalPriceAfterDiscounts = $priceSummaryAfterDiscounts->final_total_price_excluding_tax;

        $applicableDiscountsValue = \with($distribution->custom_discount, function (?float $customDiscountValue) use ($totalPriceAfterMargin, $totalPriceAfterMarginWithoutCustomDiscount, $applicableDiscounts) {
            if (!is_null($customDiscountValue)) {
                return $totalPriceAfterMarginWithoutCustomDiscount - $totalPriceAfterMargin;
            }

            return $this->calculateApplicableDiscountsValue($applicableDiscounts);
        });

        return PriceSummaryData::immutable([
            'total_price' => $quoteTotalPrice,
            'total_price_after_margin' => $totalPriceAfterMarginWithoutCustomDiscount,
            'buy_price' => $quoteBuyPrice,
            'final_total_price' => WorldwideQuoteCalc::calculateTotalPriceAfterTax($totalPriceAfterDiscounts, (float) $distribution->tax_value),
            'final_total_price_excluding_tax' => $totalPriceAfterDiscounts,
            'applicable_discounts_value' => $applicableDiscountsValue,
            'raw_margin' => $rawMarginPercentage,
            'final_margin' => WorldwideQuoteCalc::calculateMarginPercentage($totalPriceAfterDiscounts, $quoteBuyPrice),
            'margin_after_custom_discount' => $marginPercentageAfterCustomDiscount,
        ]);
    }

    public function calculateTotalPriceOfDistributorQuote(WorldwideDistribution $distribution): float
    {
        return (float) $this->queries->distributionTotalPriceQuery($distribution)->value('total_price');
    }

    public function calculatePriceSummaryAfterMarginTax(WorldwideDistribution $distribution, ImmutableMarginTaxData $marginTaxData): ImmutablePriceSummaryData
    {
        $violations = $this->validator->validate($marginTaxData);

        if (count($violations)) {
            throw new ValidationFailedException($marginTaxData, $violations);
        }

        $distributionBuyPrice = $this->calculateBuyPriceOfDistributorQuote($distribution);

        $distributionTotalPrice = $this->calculateTotalPriceOfDistributorQuote($distribution);

        $totalPriceAfterMarginWithoutCustomDiscount = WorldwideQuoteCalc::calculateTotalPriceAfterBottomUp($distributionTotalPrice, $distributionBuyPrice, (float) $marginTaxData->margin_value);

        $predefinedDiscounts = $this->predefinedDistributionDiscountsToApplicableDiscounts($distribution);

        $priceSummaryAfterPredefinedDiscounts = $this->calculatePriceSummaryAfterPredefinedDiscounts(
            QuotePriceInputData::immutable([
                'total_price' => $distributionTotalPrice,
                'buy_price' => $distributionBuyPrice,
                'margin_value' => (float) $marginTaxData->margin_value - (float) $distribution->custom_discount,
                'tax_value' => (float) $marginTaxData->tax_value,
            ]),
            $predefinedDiscounts
        );

        $totalPriceAfterPredefinedDiscounts = $priceSummaryAfterPredefinedDiscounts->final_total_price_excluding_tax;

        $finalTotalPrice = WorldwideQuoteCalc::calculateTotalPriceAfterTax($totalPriceAfterPredefinedDiscounts, (float) $marginTaxData->tax_value);

        $marginValueAfterTax = WorldwideQuoteCalc::calculateMarginPercentage($totalPriceAfterPredefinedDiscounts, $distributionBuyPrice);

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

        $distributionTotalPrice = (float) $this->queries->distributionTotalPriceQuery($distribution)->value('total_price');

        $distributionBuyPrice = $this->calculateBuyPriceOfDistributorQuote($distribution);

        $rawMarginPercentage = WorldwideQuoteCalc::calculateMarginPercentage($distributionTotalPrice, $distributionBuyPrice);

        $totalPriceAfterMargin = WorldwideQuoteCalc::calculateTotalPriceAfterBottomUp($distributionTotalPrice, $distributionBuyPrice, (float) $distribution->margin_value);

        $totalPriceAfterCustomDiscount = WorldwideQuoteCalc::calculateTotalPriceAfterBottomUp($distributionTotalPrice, $distributionBuyPrice, (float) $distribution->margin_value - $discountData->value);

        $marginValueAfterCustomDiscount = WorldwideQuoteCalc::calculateMarginPercentage($totalPriceAfterCustomDiscount, $distributionBuyPrice);

        $totalPriceAfterCustomDiscountAndTax = WorldwideQuoteCalc::calculateTotalPriceAfterTax($totalPriceAfterCustomDiscount, (float) $distribution->tax_value);

        return new ImmutablePriceSummaryData(new PriceSummaryData([
            'total_price' => $distributionTotalPrice,
            'total_price_after_margin' => $totalPriceAfterMargin,
            'final_total_price' => $totalPriceAfterCustomDiscountAndTax,
            'final_total_price_excluding_tax' => $totalPriceAfterCustomDiscount,
            'raw_margin' => $rawMarginPercentage,
            'buy_price' => $distributionBuyPrice,
            'margin_after_custom_discount' => $marginValueAfterCustomDiscount,
            'applicable_discounts_value' => $totalPriceAfterMargin - $totalPriceAfterCustomDiscount,
        ]));
    }

    public function calculatePriceSummaryOfDistributorQuoteAfterPredefinedDiscounts(WorldwideDistribution $distribution, ApplicablePredefinedDiscounts $predefinedDiscounts): ImmutablePriceSummaryData
    {
        $priceInput = QuotePriceInputData::immutable([
            'total_price' => $this->calculateTotalPriceOfDistributorQuote($distribution),
            'buy_price' => $this->calculateBuyPriceOfDistributorQuote($distribution),
            'margin_value' => (float) $distribution->margin_value,
            'tax_value' => (float) $distribution->tax_value,
        ]);

        return $this->calculatePriceSummaryAfterPredefinedDiscounts($priceInput, $predefinedDiscounts);
    }

    public function calculatePriceSummaryAfterPredefinedDiscounts(ImmutableQuotePriceInputData $priceInputData, ApplicablePredefinedDiscounts $predefinedDiscounts): ImmutablePriceSummaryData
    {
        $violations = $this->validator->validate($predefinedDiscounts);

        if (count($violations)) {
            throw new ValidationFailedException($predefinedDiscounts, $violations);
        }

        $totalPriceAfterMargin = WorldwideQuoteCalc::calculateTotalPriceAfterBottomUp(
            $priceInputData->total_price,
            $priceInputData->buy_price,
            $priceInputData->margin_value
        );

        $priceSummary = PriceSummaryData::immutable([
            'total_price' => $priceInputData->total_price,
            'total_price_after_margin' => $totalPriceAfterMargin,
            'final_total_price' => $totalPriceAfterMargin,
            'buy_price' => $priceInputData->buy_price,
        ]);

        return tap($priceSummary, function (ImmutablePriceSummaryData $priceSummary) use ($predefinedDiscounts, $priceInputData, $totalPriceAfterMargin) {
            $pipes = [];

            WorldwideQuoteCalc::addDiscountToPipe($predefinedDiscounts->multi_year_discount, $pipes);
            WorldwideQuoteCalc::addDiscountToPipe($predefinedDiscounts->pre_pay_discount, $pipes);
            WorldwideQuoteCalc::addDiscountToPipe($predefinedDiscounts->promotional_discount, $pipes);
            WorldwideQuoteCalc::addDiscountToPipe($predefinedDiscounts->special_negotiation_discount, $pipes);

            $this->pipeline
                ->send($priceSummary)
                ->through($pipes)
                ->thenReturn();

            $priceSummary->setApplicableDiscountsValue(
                $totalPriceAfterMargin - $priceSummary->final_total_price
            );

            $priceSummary->setFinalMargin(
                WorldwideQuoteCalc::calculateMarginPercentage($priceSummary->final_total_price, $priceInputData->buy_price)
            );

            $priceSummary->setFinalTotalPriceExcludingTax($priceSummary->final_total_price);

            $finalTotalPriceAfterTax = WorldwideQuoteCalc::calculateTotalPriceAfterTax($priceSummary->final_total_price, $priceInputData->tax_value);

            $priceSummary->setFinalTotalPrice($finalTotalPriceAfterTax);
        });
    }
}
