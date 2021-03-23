<?php

namespace App\Services;

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
    WorldwideQuote\ContractQuotePriceSummaryData,
    WorldwideQuote\DistributorQuoteCountryMarginTaxCollection,
    WorldwideQuote\DistributorQuoteDiscountCollection,
    WorldwideQuote\DistributorQuoteDiscountData,
    WorldwideQuote\ImmutableQuotePriceInputData,
    WorldwideQuote\PackQuoteDiscountData,
    WorldwideQuote\PackQuotePriceSummaryData,
    WorldwideQuote\QuoteFinalTotalPrice,
    WorldwideQuote\QuotePriceInputData
};
use App\Models\{Quote\Discount\MultiYearDiscount,
    Quote\Discount\PrePayDiscount,
    Quote\Discount\PromotionalDiscount,
    Quote\Discount\SND,
    Quote\WorldwideDistribution,
    Quote\WorldwideQuote
};
use App\Queries\{WorldwideDistributionQueries, WorldwideQuoteQueries};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pipeline\Pipeline;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

// TODO: implement pipeline to calculate final total_price
// TODO: which accepts the following DTO class:
// total_price
// final_total_price
// buy_price
// margin_value
// tax_value
// custom_discount_value
// sn_discount
// margin_after_sn_discount
// pre_pay_discount
// margin_after_pre_pay_discount
// promo_discount
// margin_after_promotional_discount
// multi_year_discount
// margin_after_multi_year_discount

// TODO: pipes:
// apply margin (apply margin without custom_discount)
// apply tax
// apply predefined discounts
class WorldwideQuoteCalc
{
    protected ValidatorInterface $validator;

    protected WorldwideQuoteQueries $quoteQueries;

    protected WorldwideDistributionQueries $distributionQueries;

    protected WorldwideDistributionCalc $distributionCalc;

    protected Pipeline $pipeline;

    public function __construct(ValidatorInterface $validator,
                                WorldwideQuoteQueries $quoteQueries,
                                WorldwideDistributionQueries $distributionQueries,
                                WorldwideDistributionCalc $distributionCalc,
                                Pipeline $pipeline)
    {
        $this->validator = $validator;
        $this->quoteQueries = $quoteQueries;
        $this->distributionQueries = $distributionQueries;
        $this->distributionCalc = $distributionCalc;
        $this->pipeline = $pipeline;
    }

    public function calculatePriceSummaryOfQuote(WorldwideQuote $quote): ImmutablePriceSummaryData
    {
        if ($quote->contract_type_id === CT_PACK) {
            return $this->calculatePriceSummaryOfPackQuote($quote);
        }

        if ($quote->contract_type_id === CT_CONTRACT) {
            return $this->calculatePriceSummaryOfContractQuote($quote);
        }

        throw new \RuntimeException('Contract Type of the Quote either is not set or unsupported to compute price summary.');
    }

    protected function calculatePriceSummaryOfPackQuote(WorldwideQuote $quote): ImmutablePriceSummaryData
    {
        $totalPrice = $this->calculatePackQuoteTotalPrice($quote);

        $buyPrice = (float)$quote->buy_price;

        $rawMarginPercentage = $this->calculateMarginPercentage($totalPrice, $buyPrice);

        $quoteTotalPrice = $this->calculatePackQuoteTotalPrice($quote);

        $totalPriceAfterMarginExcludingCustomDiscount = $this->calculateTotalPriceAfterMargin($quoteTotalPrice, (float)$quote->margin_value, 0.00);

        $totalPriceAfterMargin = $this->calculateTotalPriceAfterMargin($quoteTotalPrice, (float)$quote->margin_value, (float)$quote->custom_discount);

        $applicableDiscounts = $this->predefinedQuoteDiscountsToApplicableDiscounts($quote);

        $totalPriceAfterDiscounts = (float)$this->calculateTotalPriceAfterPredefinedDiscounts($totalPriceAfterMargin, $applicableDiscounts);

        $applicableDiscountsValue = with($quote->custom_discount, function (?float $customDiscountValue) use ($totalPriceAfterMargin, $totalPriceAfterMarginExcludingCustomDiscount, $applicableDiscounts) {
            if (!is_null($customDiscountValue)) {
                return $totalPriceAfterMarginExcludingCustomDiscount - $totalPriceAfterMargin;
            }

            return $this->calculateApplicableDiscountsValue($applicableDiscounts);
        });

        $finalTotalPriceValue = $this->calculateTotalPriceAfterTax($totalPriceAfterDiscounts, (float)$quote->tax_value);

        $finalMarginPercentage = $this->calculateMarginPercentage($totalPriceAfterDiscounts, $buyPrice);

        return PriceSummaryData::immutable([
            'total_price' => $totalPrice,
            'buy_price' => $buyPrice,
            'final_total_price' => $finalTotalPriceValue,
            'final_total_price_excluding_tax' => $totalPriceAfterDiscounts,
            'applicable_discounts_value' => $applicableDiscountsValue,
            'raw_margin' => $rawMarginPercentage,
            'final_margin' => $finalMarginPercentage
        ]);
    }

    protected function calculatePriceSummaryOfContractQuote(WorldwideQuote $quote): ImmutablePriceSummaryData
    {
        $quoteTotalPrice = 0.0;
        $quoteBuyPrice = 0.0;
        $quoteFinalTotalPrice = 0.0;
        $quoteFinalTotalPriceExcludingTax = 0.0;
        $quoteApplicableDiscountsValue = 0.0;
        $quoteRawMargin = null;
        $quoteFinalMargin = null;

        foreach ($quote->worldwideDistributions as $distributorQuote) {

            $distributorPriceSummary = $this->distributionCalc->calculatePriceSummaryOfDistributorQuote($distributorQuote);

            $quoteTotalPrice += $distributorPriceSummary->total_price;
            $quoteBuyPrice += $distributorPriceSummary->buy_price;
            $quoteFinalTotalPrice += $distributorPriceSummary->final_total_price;
            $quoteFinalTotalPriceExcludingTax += $distributorPriceSummary->final_total_price_excluding_tax;
            $quoteApplicableDiscountsValue += $distributorPriceSummary->applicable_discounts_value;

        }

        $quoteRawMargin = $this->calculateMarginPercentage($quoteTotalPrice, $quoteBuyPrice);
        $quoteFinalMargin = $this->calculateMarginPercentage($quoteFinalTotalPriceExcludingTax, $quoteBuyPrice);

        return PriceSummaryData::immutable([
            'total_price' => $quoteTotalPrice,
            'buy_price' => $quoteBuyPrice,
            'final_total_price' => $quoteFinalTotalPrice,
            'final_total_price_excluding_tax' => $quoteFinalTotalPriceExcludingTax,
            'applicable_discounts_value' => $quoteApplicableDiscountsValue,
            'raw_margin' => $quoteRawMargin,
            'final_margin' => $quoteFinalMargin,
        ]);
    }

    public function calculateQuoteTotalPrice(WorldwideQuote $quote): float
    {
        if ($quote->contract_type_id === CT_PACK) {
            return $this->calculatePackQuoteTotalPrice($quote);
        }

        if ($quote->contract_type_id === CT_CONTRACT) {
            return $this->calculateContractQuoteTotalPrice($quote);
        }

        throw new \RuntimeException('Contract Type of the Quote either is not set or unsupported to calculate a total price.');
    }

    public function calculateContractQuoteTotalPrice(WorldwideQuote $quote): float
    {
        /** @var Collection|WorldwideDistribution[] $distributions */

        $distributions = $quote->worldwideDistributions()->get(['id', 'worldwide_quote_id', 'distributor_file_id', 'use_groups']);

        return (float)$distributions->reduce(function (float $totalPrice, WorldwideDistribution $distribution) {
            $distributionTotalPrice = (float)$this->distributionQueries->distributionTotalPriceQuery($distribution)->value('total_price');

            return $totalPrice + $distributionTotalPrice;
        }, 0.0);
    }

    public function calculatePackQuoteTotalPrice(WorldwideQuote $quote): float
    {
        return (float)$quote->assets()->sum('price');
    }

    public function calculateQuoteFinalTotalPrice(WorldwideQuote $quote): QuoteFinalTotalPrice
    {
        if ($quote->contract_type_id === CT_PACK) {
            return $this->calculatePackQuoteFinalTotalPrice($quote);
        }

        if ($quote->contract_type_id === CT_CONTRACT) {
            return $this->calculateContractQuoteFinalTotalPrice($quote);
        }

        throw new \RuntimeException('Contract Type of the Quote either is not set or unsupported to calculate a final total price.');
    }

    public function calculateTotalPriceAfterMargin(float $totalPrice, float $marginValue, float $customDiscount): float
    {
        $finalMargin = ($marginValue - $customDiscount) / 100;

        $marginDivider = $finalMargin >= 1
            ? 1 / ($finalMargin + 1)
            : 1 - $finalMargin;

        return $totalPrice / $marginDivider;
    }

    public function calculateTotalPriceAfterTax(float $totalPrice, float $taxValue): float
    {
        return $totalPrice + $taxValue;
    }

    public function calculateContractQuotePriceSummaryAfterCountryMarginTax(WorldwideQuote $quote, DistributorQuoteCountryMarginTaxCollection $collection): ContractQuotePriceSummaryData
    {
        foreach ($collection as $marginTaxData) {
            $violations = $this->validator->validate($marginTaxData);

            if (count($violations)) {
                throw new ValidationFailedException($marginTaxData, $violations);
            }
        }

        $collection->rewind();

        $distributorQuotePriceSummaryCollection = [];
        $quoteTotalPrice = 0.0;
        $quoteFinalTotalPrice = 0.0;
        $quoteFinalTotalPriceExcludingTax = 0.0;
        $quoteTotalBuyPrice = 0.0;

        foreach ($collection as $distributorQuoteData) {

            $distributorQuotePriceSummary = $this->distributionCalc->calculatePriceSummaryAfterMarginTax(
                $distributorQuoteData->worldwide_distribution,
                $distributorQuoteData->margin_tax_data
            );

            $distributorQuotePriceSummaryCollection[] = [
                'worldwide_distribution_id' => $distributorQuoteData->worldwide_distribution->getKey(),
                'index' => $distributorQuoteData->index,
                'price_summary' => $distributorQuotePriceSummary
            ];

            $quoteTotalPrice += $distributorQuotePriceSummary->total_price;
            $quoteFinalTotalPrice += $distributorQuotePriceSummary->final_total_price;
            $quoteFinalTotalPriceExcludingTax += $distributorQuotePriceSummary->final_total_price_excluding_tax;
            $quoteTotalBuyPrice += $distributorQuotePriceSummary->buy_price;

        }

        $quoteMarginValue = $this->calculateMarginPercentage($quoteFinalTotalPriceExcludingTax, $quoteTotalBuyPrice);

        $quotePriceSummary = PriceSummaryData::immutable([
            'total_price' => $quoteTotalPrice,
            'final_total_price' => $quoteFinalTotalPrice,
            'final_total_price_excluding_tax' => $quoteFinalTotalPriceExcludingTax,
            'buy_price' => $quoteTotalBuyPrice,
            'final_margin' => $quoteMarginValue
        ]);

        return new ContractQuotePriceSummaryData([
            'worldwide_quote_id' => $quote->getKey(),
            'quote_price_summary' => $quotePriceSummary,
            'worldwide_distributions' => $distributorQuotePriceSummaryCollection
        ]);
    }

    public function calculateContractQuotePriceSummaryAfterDiscounts(WorldwideQuote $quote, DistributorQuoteDiscountCollection $collection): ContractQuotePriceSummaryData
    {
        foreach ($collection as $marginTaxData) {
            $violations = $this->validator->validate($marginTaxData);

            if (count($violations)) {
                throw new ValidationFailedException($marginTaxData, $violations);
            }
        }

        $collection->rewind();

        $distributorQuotePriceSummaryCollection = [];
        $quoteTotalPrice = 0.0;
        $quoteFinalTotalPrice = 0.0;
        $quoteFinalTotalPriceExcludingTax = 0.0;
        $quoteTotalBuyPrice = 0.0;
        $quoteApplicableDiscounts = 0.0;

        foreach ($collection as $distributorQuoteData) {

            /** @var ImmutablePriceSummaryData $distributorQuotePriceSummary */
            $distributorQuotePriceSummary = with($distributorQuoteData, function (DistributorQuoteDiscountData $discountData): ImmutablePriceSummaryData {
                if (!is_null($discountData->custom_discount)) {
                    return $this->distributionCalc->calculatePriceSummaryAfterCustomDiscount(
                        $discountData->worldwide_distribution,
                        $discountData->custom_discount
                    );
                }

                return $this->distributionCalc->calculatePriceSummaryAfterPredefinedDiscounts(
                    $discountData->worldwide_distribution,
                    $discountData->predefined_discounts
                );
            });

            $distributorQuotePriceSummaryCollection[] = [
                'worldwide_distribution_id' => $distributorQuoteData->worldwide_distribution->getKey(),
                'index' => $distributorQuoteData->index,
                'price_summary' => $distributorQuotePriceSummary
            ];

            $quoteTotalPrice += $distributorQuotePriceSummary->total_price;
            $quoteFinalTotalPrice += $distributorQuotePriceSummary->final_total_price;
            $quoteFinalTotalPriceExcludingTax += $distributorQuotePriceSummary->final_total_price_excluding_tax;
            $quoteTotalBuyPrice += $distributorQuotePriceSummary->buy_price;
            $quoteApplicableDiscounts += $distributorQuotePriceSummary->applicable_discounts_value;
        }

        $quoteMarginValue = $this->calculateMarginPercentage($quoteFinalTotalPriceExcludingTax, $quoteTotalBuyPrice);

        $quotePriceSummary = PriceSummaryData::immutable([
            'total_price' => $quoteTotalPrice,
            'final_total_price' => $quoteFinalTotalPrice,
            'final_total_price_excluding_tax' => $quoteFinalTotalPriceExcludingTax,
            'buy_price' => $quoteTotalBuyPrice,
            'final_margin' => $quoteMarginValue,
            'applicable_discounts_value' => $quoteApplicableDiscounts,
        ]);

        return new ContractQuotePriceSummaryData([
            'worldwide_quote_id' => $quote->getKey(),
            'quote_price_summary' => $quotePriceSummary,
            'worldwide_distributions' => $distributorQuotePriceSummaryCollection
        ]);
    }

    public function calculatePackQuotePriceSummaryAfterDiscounts(WorldwideQuote $quote, PackQuoteDiscountData $discountData): PackQuotePriceSummaryData
    {
        $quoteTotalPrice = $this->calculatePackQuoteTotalPrice($quote);

        $quoteTotalBuyPrice = (float)$quote->buy_price;

        $priceInputData = QuotePriceInputData::immutable([
            'total_price' => $quoteTotalPrice,
            'buy_price' => $quoteTotalBuyPrice,
            'margin_value' => (float)$quote->margin_value,
            'tax_value' => (float)$quote->tax_value,
        ]);

        /** @var ImmutablePriceSummaryData $priceSummaryAfterDiscounts */
        $priceSummaryAfterDiscounts = with($discountData, function (PackQuoteDiscountData $discountData) use ($priceInputData): ImmutablePriceSummaryData {
            if (!is_null($discountData->custom_discount)) {
                return $this->calculatePriceSummaryAfterCustomDiscount(
                    $priceInputData,
                    $discountData->custom_discount
                );
            }

            return $this->calculatePriceSummaryAfterPredefinedDiscounts(
                $priceInputData,
                $discountData->predefined_discounts
            );
        });

        return new PackQuotePriceSummaryData([
            'worldwide_quote_id' => $quote->getKey(),
            'quote_price_summary' => $priceSummaryAfterDiscounts,
        ]);
    }

    public function calculatePriceSummaryAfterCustomDiscount(ImmutableQuotePriceInputData $priceInputData, ImmutableCustomDiscountData $discountData): ImmutablePriceSummaryData
    {
        $violations = $this->validator->validate($discountData);

        if (count($violations)) {
            throw new ValidationFailedException($discountData, $violations);
        }

        $totalPriceAfterMargin = $this->calculateTotalPriceAfterMargin($priceInputData->total_price, $priceInputData->margin_value, 0.0);

        $totalPriceAfterCustomDiscount = $this->calculateTotalPriceAfterMargin($priceInputData->total_price, $priceInputData->margin_value, $discountData->value);

        $totalPriceAfterCustomDiscountAndTax = $this->calculateTotalPriceAfterTax($totalPriceAfterCustomDiscount, $priceInputData->tax_value);

        $marginValueAfterCustomDiscount = $this->calculateMarginPercentage($totalPriceAfterCustomDiscount, $priceInputData->buy_price);

        return PriceSummaryData::immutable([
            'total_price' => $priceInputData->total_price,
            'final_total_price' => $totalPriceAfterCustomDiscountAndTax,
            'buy_price' => $priceInputData->buy_price,
            'margin_after_custom_discount' => $marginValueAfterCustomDiscount,
            'applicable_discounts_value' => $totalPriceAfterMargin - $totalPriceAfterCustomDiscount,
            'final_margin' => $marginValueAfterCustomDiscount
        ]);
    }

    public function calculatePriceSummaryAfterPredefinedDiscounts(ImmutableQuotePriceInputData $priceInputData, ApplicablePredefinedDiscounts $predefinedDiscounts): ImmutablePriceSummaryData
    {
        $violations = $this->validator->validate($predefinedDiscounts);

        if (count($violations)) {
            throw new ValidationFailedException($predefinedDiscounts, $violations);
        }

        $totalPriceAfterMargin = $this->calculateTotalPriceAfterMargin($priceInputData->total_price, $priceInputData->margin_value, 0.0);

        $priceSummary = PriceSummaryData::immutable([
            'total_price' => $priceInputData->total_price,
            'final_total_price' => $totalPriceAfterMargin,
            'buy_price' => $priceInputData->buy_price,
        ]);

        $pipes = [];

        if (!is_null($predefinedDiscounts->multi_year_discount)) {
            $pipes[] = $this->calculateMarginAfterMultiYearDiscountPipe($predefinedDiscounts->multi_year_discount);
        }

        if (!is_null($predefinedDiscounts->pre_pay_discount)) {
            $pipes[] = $this->calculateMarginAfterPrePayDiscountPipe($predefinedDiscounts->pre_pay_discount);
        }

        if (!is_null($predefinedDiscounts->promotional_discount)) {
            $pipes[] = $this->calculateMarginAfterPromotionalDiscountPipe($predefinedDiscounts->promotional_discount);
        }

        if (!is_null($predefinedDiscounts->special_negotiation_discount)) {
            $pipes[] = $this->calculateMarginAfterSpecialNegotiationDiscountPipe($predefinedDiscounts->special_negotiation_discount);
        }

        $this->pipeline
            ->send($priceSummary)
            ->through($pipes)
            ->thenReturn();

        $priceSummary->setApplicableDiscountsValue(
            $totalPriceAfterMargin - $priceSummary->final_total_price
        );

        $priceSummary->setFinalMargin(
            $this->calculateMarginPercentage($priceSummary->final_total_price, $priceInputData->buy_price)
        );

        $finalTotalPriceAfterTax = $this->calculateTotalPriceAfterTax($priceSummary->final_total_price, $priceInputData->tax_value);

        $priceSummary->setFinalTotalPrice($finalTotalPriceAfterTax);

        return $priceSummary;
    }

    public function calculatePackQuotePriceSummaryAfterCountryMarginTax(WorldwideQuote $quote, ImmutableMarginTaxData $marginTaxData): PackQuotePriceSummaryData
    {
        $quoteTotalPrice = $this->calculatePackQuoteTotalPrice($quote);

        $totalPriceAfterMargin = $this->calculateTotalPriceAfterMargin($quoteTotalPrice, (float)$marginTaxData->margin_value, (float)$quote->custom_discount);

        $applicableDiscounts = $this->predefinedQuoteDiscountsToApplicableDiscounts($quote);

        $totalPriceAfterDiscounts = (float)$this->calculateTotalPriceAfterPredefinedDiscounts($totalPriceAfterMargin, $applicableDiscounts);

        $finalTotalPrice = $this->calculateTotalPriceAfterTax($totalPriceAfterDiscounts, (float)$marginTaxData->tax_value);

        $buyPrice = (float)$quote->buy_price;

        $quoteMarginValue = $this->calculateMarginPercentage($finalTotalPrice, $buyPrice);

        $quotePriceSummary = PriceSummaryData::immutable([
            'total_price' => $quoteTotalPrice,
            'final_total_price' => $finalTotalPrice,
            'buy_price' => $buyPrice,
            'final_margin' => $quoteMarginValue
        ]);

        return new PackQuotePriceSummaryData([
            'worldwide_quote_id' => $quote->getKey(),
            'quote_price_summary' => $quotePriceSummary
        ]);
    }

    public function calculatePackQuoteFinalTotalPrice(WorldwideQuote $quote): QuoteFinalTotalPrice
    {
        $quoteTotalPrice = $this->calculatePackQuoteTotalPrice($quote);

        $totalPriceAfterMargin = $this->calculateTotalPriceAfterMargin($quoteTotalPrice, (float)$quote->margin_value, (float)$quote->custom_discount);

        $applicableDiscounts = $this->predefinedQuoteDiscountsToApplicableDiscounts($quote);

        $totalPriceAfterDiscounts = (float)$this->calculateTotalPriceAfterPredefinedDiscounts($totalPriceAfterMargin, $applicableDiscounts);

        $applicableDiscountsValue = with($quote->custom_discount, function (?float $customDiscountValue) use ($quoteTotalPrice, $totalPriceAfterDiscounts, $applicableDiscounts) {
            if (!is_null($customDiscountValue)) {
                return $quoteTotalPrice - $totalPriceAfterDiscounts;
            }

            return $this->calculateApplicableDiscountsValue($applicableDiscounts);
        });

        $finalTotalPriceValue = $this->calculateTotalPriceAfterTax($totalPriceAfterMargin, (float)$quote->tax_value);

        return new QuoteFinalTotalPrice([
            'final_total_price_value' => $finalTotalPriceValue,
            'applicable_discounts_value' => $applicableDiscountsValue,
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

    public function calculateContractQuoteFinalTotalPrice(WorldwideQuote $quote): QuoteFinalTotalPrice
    {
        return $quote->worldwideDistributions->reduce(function (QuoteFinalTotalPrice $quoteFinalTotalPrice, WorldwideDistribution $distribution) {
            if (is_null($distribution->total_price)) {
                $distribution->total_price = $this->distributionCalc->calculateDistributionTotalPrice($distribution);
            }

            if (is_null($distribution->final_total_price) || is_null($distribution->applicable_discounts_value)) {
                $finalTotalPrice = $this->distributionCalc->calculateDistributionFinalTotalPrice($distribution, $distribution->total_price);

                $distribution->final_total_price = $finalTotalPrice->final_total_price_value;
                $distribution->applicable_discounts_value = $finalTotalPrice->applicable_discounts_value;
            }

            $quoteFinalTotalPrice->final_total_price_value += (float)$distribution->final_total_price;
            $quoteFinalTotalPrice->applicable_discounts_value += (float)$distribution->applicable_discounts_value;

            return $quoteFinalTotalPrice;
        }, new QuoteFinalTotalPrice());
    }

    public function calculateQuoteBuyPrice(WorldwideQuote $quote): float
    {
        if ($quote->contract_type_id === CT_PACK) {
            return (float)$quote->buy_price;
        }

        if ($quote->contract_type_id === CT_CONTRACT) {
            return (float)$quote->worldwideDistributions()->getQuery()->sum('buy_price');
        }

        throw new \RuntimeException('Unsupported Contract Type of the Quote to calculate a buy price.');
    }

    public function predefinedQuoteDiscountsToApplicableDiscounts(WorldwideQuote $quote): ApplicablePredefinedDiscounts
    {
        return new ApplicablePredefinedDiscounts([
            'multi_year_discount' => transform($quote->multiYearDiscount, [static::class, 'multiYearDiscountToImmutableMultiYearDiscountData']),
            'pre_pay_discount' => transform($quote->prePayDiscount, [static::class, 'prePayDiscountToImmutablePrePayDiscountData']),
            'promotional_discount' => transform($quote->promotionalDiscount, [static::class, 'promotionalDiscountToImmutablePromotionalDiscountData']),
            'special_negotiation_discount' => transform($quote->snDiscount, [static::class, 'snDiscountToImmutableSpecialNegotiationData']),
        ]);
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

    public function calculateMarginPercentage(float $totalPrice, float $buyPrice): float
    {
        if ($totalPrice === 0.0) {
            return 0;
        }

        return (($totalPrice - $buyPrice) / $totalPrice) * 100;
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
}
