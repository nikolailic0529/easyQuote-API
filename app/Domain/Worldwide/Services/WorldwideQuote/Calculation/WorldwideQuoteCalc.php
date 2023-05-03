<?php

namespace App\Domain\Worldwide\Services\WorldwideQuote\Calculation;

use App\Domain\Discount\DataTransferObjects\ApplicablePredefinedDiscounts;
use App\Domain\Discount\DataTransferObjects\ImmutableCustomDiscountData;
use App\Domain\Discount\DataTransferObjects\ImmutableMultiYearDiscountData;
use App\Domain\Discount\DataTransferObjects\ImmutablePrePayDiscountData;
use App\Domain\Discount\DataTransferObjects\ImmutablePriceSummaryData;
use App\Domain\Discount\DataTransferObjects\ImmutablePromotionalDiscountData;
use App\Domain\Discount\DataTransferObjects\ImmutableSpecialNegotiationDiscountData;
use App\Domain\Discount\DataTransferObjects\MultiYearDiscountData;
use App\Domain\Discount\DataTransferObjects\PrePayDiscountData;
use App\Domain\Discount\DataTransferObjects\PromotionalDiscountData;
use App\Domain\Discount\DataTransferObjects\SpecialNegotiationDiscountData;
use App\Domain\Discount\Models\MultiYearDiscount;
use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\Discount\Models\PromotionalDiscount;
use App\Domain\Discount\Models\SND;
use App\Domain\ExchangeRate\Services\CurrencyConverter;
use App\Domain\Margin\DataTransferObjects\ImmutableMarginTaxData;
use App\Domain\Worldwide\DataTransferObjects\Calc\{PriceSummaryData};
use App\Domain\Worldwide\DataTransferObjects\Quote\ContractQuotePriceSummaryData;
use App\Domain\Worldwide\DataTransferObjects\Quote\DistributorQuoteCountryMarginTaxCollection;
use App\Domain\Worldwide\DataTransferObjects\Quote\DistributorQuoteDiscountCollection;
use App\Domain\Worldwide\DataTransferObjects\Quote\DistributorQuoteDiscountData;
use App\Domain\Worldwide\DataTransferObjects\Quote\ImmutableQuotePriceInputData;
use App\Domain\Worldwide\DataTransferObjects\Quote\PackQuoteDiscountData;
use App\Domain\Worldwide\DataTransferObjects\Quote\PackQuotePriceSummaryData;
use App\Domain\Worldwide\DataTransferObjects\Quote\QuoteFinalTotalPrice;
use App\Domain\Worldwide\DataTransferObjects\Quote\QuotePriceInputData;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteAsset;
use App\Domain\Worldwide\Models\{WorldwideQuoteAssetsGroup};
use App\Domain\Worldwide\Queries\WorldwideDistributionQueries;
use App\Domain\Worldwide\Queries\{WorldwideQuoteQueries};
use App\Domain\Worldwide\Services\WorldwideQuote\Calculation\Pipes\ApplyMultiYearDiscountPipe;
use App\Domain\Worldwide\Services\WorldwideQuote\Calculation\Pipes\ApplyPrePayDiscountPipe;
use App\Domain\Worldwide\Services\WorldwideQuote\Calculation\Pipes\ApplyPromotionalDiscountPipe;
use App\Domain\Worldwide\Services\WorldwideQuote\Calculation\Pipes\ApplySpecialNegotiationDiscountPipe;
use App\Domain\Worldwide\Services\WorldwideQuote\Exceptions\ContractTypeException;
use App\Domain\Worldwide\Services\WorldwideQuote\Exceptions\DiscountException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pipeline\Pipeline;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WorldwideQuoteCalc
{
    public function __construct(protected ValidatorInterface $validator,
                                protected CurrencyConverter $currencyConverter,
                                protected WorldwideQuoteQueries $quoteQueries,
                                protected WorldwideDistributionQueries $distributionQueries,
                                protected WorldwideDistributorQuoteCalc $distributionCalc,
                                protected Pipeline $pipeline)
    {
    }

    public static function multiYearDiscountToImmutableMultiYearDiscountData(MultiYearDiscount $discount): ImmutableMultiYearDiscountData
    {
        return new ImmutableMultiYearDiscountData(
            new MultiYearDiscountData(['value' => (float) \data_get($discount->durations, 'duration.value')])
        );
    }

    public static function prePayDiscountToImmutablePrePayDiscountData(PrePayDiscount $discount): ImmutablePrePayDiscountData
    {
        return new ImmutablePrePayDiscountData(
            new PrePayDiscountData(['value' => (float) \data_get($discount->durations, 'duration.value')])
        );
    }

    public static function promotionalDiscountToImmutablePromotionalDiscountData(PromotionalDiscount $discount): ImmutablePromotionalDiscountData
    {
        return new ImmutablePromotionalDiscountData(
            new PromotionalDiscountData(['value' => (float) $discount->value, 'minimum_limit' => (float) $discount->minimum_limit])
        );
    }

    public static function snDiscountToImmutableSpecialNegotiationData(SND $discount): ImmutableSpecialNegotiationDiscountData
    {
        return new ImmutableSpecialNegotiationDiscountData(
            new SpecialNegotiationDiscountData(['value' => (float) $discount->value])
        );
    }

    public function calculatePriceSummaryOfQuote(WorldwideQuote $quote): ImmutablePriceSummaryData
    {
        return match ($quote->contract_type_id) {
            \CT_PACK => $this->calculatePriceSummaryOfPackQuote($quote),
            \CT_CONTRACT => $this->calculatePriceSummaryOfContractQuote($quote),
            default => throw ContractTypeException::unsupportedContractType(),
        };
    }

    protected function calculatePriceSummaryOfPackQuote(WorldwideQuote $quote): ImmutablePriceSummaryData
    {
        $totalPrice = $this->calculatePackQuoteTotalPrice($quote);

        $buyPrice = $this->calculatePackQuoteBuyPrice($quote);

        $rawMarginPercentage = $this->calculateMarginPercentage($totalPrice, $buyPrice);

        $totalPriceAfterMarginExcludingCustomDiscount = self::calculateTotalPriceAfterBottomUp(
            $totalPrice,
            $buyPrice,
            (float) $quote->activeVersion->margin_value,
        );

        $totalPriceAfterMargin = self::calculateTotalPriceAfterBottomUp(
            $totalPrice,
            $buyPrice,
            (float) $quote->activeVersion->margin_value - (float) $quote->activeVersion->custom_discount,
        );

        $predefinedDiscounts = $this->predefinedQuoteDiscountsToApplicableDiscounts($quote);

        $priceSummaryAfterPredefinedDiscounts = $this->calculatePriceSummaryAfterPredefinedDiscounts(
            QuotePriceInputData::immutable([
                'total_price' => $totalPrice,
                'buy_price' => $buyPrice,
                'margin_value' => (float) $quote->activeVersion->margin_value - (float) $quote->activeVersion->custom_discount,
                'tax_value' => (float) $quote->activeVersion->tax_value,
            ]),
            $predefinedDiscounts
        );

        $totalPriceAfterDiscounts = $priceSummaryAfterPredefinedDiscounts->final_total_price_excluding_tax;

        $applicableDiscountsValue = \with($quote->activeVersion->custom_discount, function (?float $customDiscountValue) use ($totalPriceAfterMargin, $totalPriceAfterMarginExcludingCustomDiscount, $predefinedDiscounts) {
            if (!is_null($customDiscountValue)) {
                return $totalPriceAfterMarginExcludingCustomDiscount - $totalPriceAfterMargin;
            }

            return $this->calculateApplicableDiscountsValue($predefinedDiscounts);
        });

        $finalTotalPriceValue = $this->calculateTotalPriceAfterTax($totalPriceAfterDiscounts, (float) $quote->activeVersion->tax_value);

        $finalMarginPercentage = $this->calculateMarginPercentage($totalPriceAfterDiscounts, $buyPrice);

        return PriceSummaryData::immutable([
            'total_price' => $totalPrice,
            'total_price_after_margin' => $totalPriceAfterMarginExcludingCustomDiscount,
            'buy_price' => $buyPrice,
            'final_total_price' => $finalTotalPriceValue,
            'final_total_price_excluding_tax' => $totalPriceAfterDiscounts,
            'applicable_discounts_value' => $applicableDiscountsValue,
            'raw_margin' => $rawMarginPercentage,
            'final_margin' => $finalMarginPercentage,
        ]);
    }

    public function calculatePackQuoteTotalPrice(WorldwideQuote $quote): float
    {
        if ($quote->activeVersion->use_groups) {
            $assetsGroups = $quote->assetsGroups()
                ->withSum('assets', 'price')
                ->where($quote->assetsGroups()->qualifyColumn('is_selected'), true)
                ->get();

            return (float) array_reduce($assetsGroups->all(), function (float $result, WorldwideQuoteAssetsGroup $assetsGroup) {
                return $result + $assetsGroup->assets_sum_price;
            }, 0.0);
        }

        return (float) $quote->activeVersion->assets()
            ->getQuery()
            ->where('is_selected', true)
            ->sum('price');
    }

    protected function calculatePackQuoteBuyPrice(WorldwideQuote $quote): float
    {
        return (float) $quote->activeVersion
            ->assets()
            ->where('is_selected', true)
            ->get()
            ->sum(function (WorldwideQuoteAsset $asset): float {
                return (float) ($asset->buy_price * ($asset->exchange_rate_value ?? 1));
            });
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

    public function predefinedQuoteDiscountsToApplicableDiscounts(WorldwideQuote $quote): ApplicablePredefinedDiscounts
    {
        return new ApplicablePredefinedDiscounts([
            'multi_year_discount' => \transform($quote->activeVersion->multiYearDiscount, [static::class, 'multiYearDiscountToImmutableMultiYearDiscountData']),
            'pre_pay_discount' => \transform($quote->activeVersion->prePayDiscount, [static::class, 'prePayDiscountToImmutablePrePayDiscountData']),
            'promotional_discount' => \transform($quote->activeVersion->promotionalDiscount, [static::class, 'promotionalDiscountToImmutablePromotionalDiscountData']),
            'special_negotiation_discount' => \transform($quote->activeVersion->snDiscount, [static::class, 'snDiscountToImmutableSpecialNegotiationData']),
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

    final public static function calculateTotalPriceAfterTax(float $totalPrice, float $taxValue): float
    {
        return $totalPrice + $taxValue;
    }

    protected function calculatePriceSummaryOfContractQuote(WorldwideQuote $quote): ImmutablePriceSummaryData
    {
        $quoteTotalPrice = 0.0;
        $quoteTotalPriceAfterMargin = 0.0;
        $quoteBuyPrice = 0.0;
        $quoteFinalTotalPrice = 0.0;
        $quoteFinalTotalPriceExcludingTax = 0.0;
        $quoteApplicableDiscountsValue = 0.0;

        foreach ($quote->activeVersion->worldwideDistributions as $distributorQuote) {
            $distributorPriceSummary = $this->distributionCalc->calculatePriceSummaryOfDistributorQuote($distributorQuote);

            $quoteTotalPrice += $distributorPriceSummary->total_price;
            $quoteTotalPriceAfterMargin += $distributorPriceSummary->total_price_after_margin;
            $quoteBuyPrice += $distributorPriceSummary->buy_price;
            $quoteFinalTotalPrice += $distributorPriceSummary->final_total_price;
            $quoteFinalTotalPriceExcludingTax += $distributorPriceSummary->final_total_price_excluding_tax;
            $quoteApplicableDiscountsValue += $distributorPriceSummary->applicable_discounts_value;
        }

        $quoteRawMargin = $this->calculateMarginPercentage($quoteTotalPrice, $quoteBuyPrice);
        $quoteFinalMargin = $this->calculateMarginPercentage($quoteFinalTotalPriceExcludingTax, $quoteBuyPrice);

        return PriceSummaryData::immutable([
            'total_price' => $quoteTotalPrice,
            'total_price_after_margin' => $quoteTotalPriceAfterMargin,
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
        return match ($quote->contract_type_id) {
            \CT_PACK => $this->calculatePackQuoteTotalPrice($quote),
            \CT_CONTRACT => $this->calculateContractQuoteTotalPrice($quote),
            default => ContractTypeException::unsupportedContractType(),
        };
    }

    public function calculateContractQuoteTotalPrice(WorldwideQuote $quote): float
    {
        /** @var Collection|\App\Domain\Worldwide\Models\WorldwideDistribution[] $distributions */
        $distributions = $quote->activeVersion->worldwideDistributions()->get(['id', 'worldwide_quote_id', 'distributor_file_id', 'use_groups']);

        return (float) $distributions->sum(function (WorldwideDistribution $distribution) {
            return (float) $this->distributionQueries->distributionTotalPriceQuery($distribution)->value('total_price');
        });
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
        $quoteTotalPriceAfterMargin = 0.0;
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
                'price_summary' => $distributorQuotePriceSummary,
            ];

            $quoteTotalPrice += $distributorQuotePriceSummary->total_price;
            $quoteTotalPriceAfterMargin += $distributorQuotePriceSummary->total_price_after_margin;
            $quoteFinalTotalPrice += $distributorQuotePriceSummary->final_total_price;
            $quoteFinalTotalPriceExcludingTax += $distributorQuotePriceSummary->final_total_price_excluding_tax;
            $quoteTotalBuyPrice += $distributorQuotePriceSummary->buy_price;
        }

        $quoteMarginValue = $this->calculateMarginPercentage($quoteFinalTotalPriceExcludingTax, $quoteTotalBuyPrice);

        $quotePriceSummary = PriceSummaryData::immutable([
            'total_price' => $quoteTotalPrice,
            'total_price_after_margin' => $quoteTotalPriceAfterMargin,
            'final_total_price' => $quoteFinalTotalPrice,
            'final_total_price_excluding_tax' => $quoteFinalTotalPriceExcludingTax,
            'buy_price' => $quoteTotalBuyPrice,
            'final_margin' => $quoteMarginValue,
        ]);

        return new ContractQuotePriceSummaryData([
            'worldwide_quote_id' => $quote->getKey(),
            'quote_price_summary' => $quotePriceSummary,
            'worldwide_distributions' => $distributorQuotePriceSummaryCollection,
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
        $quoteTotalPriceAfterMargin = 0.0;
        $quoteFinalTotalPrice = 0.0;
        $quoteFinalTotalPriceExcludingTax = 0.0;
        $quoteTotalBuyPrice = 0.0;
        $quoteApplicableDiscounts = 0.0;

        foreach ($collection as $distributorQuoteData) {
            /** @var ImmutablePriceSummaryData $distributorQuotePriceSummary */
            $distributorQuotePriceSummary = \with($distributorQuoteData, function (DistributorQuoteDiscountData $discountData): ImmutablePriceSummaryData {
                if (!is_null($discountData->custom_discount)) {
                    return $this->distributionCalc->calculatePriceSummaryAfterCustomDiscount(
                        $discountData->worldwide_distribution,
                        $discountData->custom_discount
                    );
                }

                return $this->distributionCalc->calculatePriceSummaryOfDistributorQuoteAfterPredefinedDiscounts(
                    $discountData->worldwide_distribution,
                    $discountData->predefined_discounts
                );
            });

            $distributorQuotePriceSummaryCollection[] = [
                'worldwide_distribution_id' => $distributorQuoteData->worldwide_distribution->getKey(),
                'index' => $distributorQuoteData->index,
                'price_summary' => $distributorQuotePriceSummary,
            ];

            $quoteTotalPrice += $distributorQuotePriceSummary->total_price;
            $quoteTotalPriceAfterMargin += $distributorQuotePriceSummary->total_price_after_margin;
            $quoteFinalTotalPrice += $distributorQuotePriceSummary->final_total_price;
            $quoteFinalTotalPriceExcludingTax += $distributorQuotePriceSummary->final_total_price_excluding_tax;
            $quoteTotalBuyPrice += $distributorQuotePriceSummary->buy_price;
            $quoteApplicableDiscounts += $distributorQuotePriceSummary->applicable_discounts_value;
        }

        $quoteMarginValue = $this->calculateMarginPercentage($quoteFinalTotalPriceExcludingTax, $quoteTotalBuyPrice);

        $quotePriceSummary = PriceSummaryData::immutable([
            'total_price' => $quoteTotalPrice,
            'total_price_after_margin' => $quoteTotalPriceAfterMargin,
            'final_total_price' => $quoteFinalTotalPrice,
            'final_total_price_excluding_tax' => $quoteFinalTotalPriceExcludingTax,
            'buy_price' => $quoteTotalBuyPrice,
            'final_margin' => $quoteMarginValue,
            'applicable_discounts_value' => $quoteApplicableDiscounts,
        ]);

        return new ContractQuotePriceSummaryData([
            'worldwide_quote_id' => $quote->getKey(),
            'quote_price_summary' => $quotePriceSummary,
            'worldwide_distributions' => $distributorQuotePriceSummaryCollection,
        ]);
    }

    public function calculatePackQuotePriceSummaryAfterDiscounts(WorldwideQuote $quote, PackQuoteDiscountData $discountData): PackQuotePriceSummaryData
    {
        $quoteTotalPrice = $this->calculatePackQuoteTotalPrice($quote);

        $quoteTotalBuyPrice = $this->calculatePackQuoteBuyPrice($quote);

        $priceInputData = QuotePriceInputData::immutable([
            'total_price' => $quoteTotalPrice,
            'buy_price' => $quoteTotalBuyPrice,
            'margin_value' => (float) $quote->activeVersion->margin_value,
            'tax_value' => (float) $quote->activeVersion->tax_value,
        ]);

        /** @var ImmutablePriceSummaryData $priceSummaryAfterDiscounts */
        $priceSummaryAfterDiscounts = \with($discountData, function (PackQuoteDiscountData $discountData) use ($priceInputData): ImmutablePriceSummaryData {
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

        $totalPriceAfterMargin = self::calculateTotalPriceAfterBottomUp(
            $priceInputData->total_price,
            $priceInputData->buy_price,
            $priceInputData->margin_value
        );

        $totalPriceAfterCustomDiscount = self::calculateTotalPriceAfterBottomUp(
            $priceInputData->total_price,
            $priceInputData->buy_price,
            $priceInputData->margin_value - $discountData->value
        );

        $totalPriceAfterCustomDiscountAndTax = $this->calculateTotalPriceAfterTax($totalPriceAfterCustomDiscount, $priceInputData->tax_value);

        $marginValueAfterCustomDiscount = $this->calculateMarginPercentage($totalPriceAfterCustomDiscount, $priceInputData->buy_price);

        return PriceSummaryData::immutable([
            'total_price' => $priceInputData->total_price,
            'total_price_after_margin' => $totalPriceAfterMargin,
            'final_total_price' => $totalPriceAfterCustomDiscountAndTax,
            'final_total_price_excluding_tax' => $totalPriceAfterCustomDiscount,
            'buy_price' => $priceInputData->buy_price,
            'margin_after_custom_discount' => $marginValueAfterCustomDiscount,
            'applicable_discounts_value' => $totalPriceAfterMargin - $totalPriceAfterCustomDiscount,
            'final_margin' => $marginValueAfterCustomDiscount,
        ]);
    }

    public function calculatePriceSummaryAfterPredefinedDiscounts(ImmutableQuotePriceInputData $priceInputData, ApplicablePredefinedDiscounts $predefinedDiscounts): ImmutablePriceSummaryData
    {
        $violations = $this->validator->validate($predefinedDiscounts);

        if (count($violations)) {
            throw new ValidationFailedException($predefinedDiscounts, $violations);
        }

        $totalPriceAfterMargin = self::calculateTotalPriceAfterBottomUp(
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

            foreach ([
                         $predefinedDiscounts->multi_year_discount,
                         $predefinedDiscounts->pre_pay_discount,
                         $predefinedDiscounts->promotional_discount,
                         $predefinedDiscounts->special_negotiation_discount,
                     ] as $discount) {
                self::addDiscountToPipe($discount, $pipes);
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

            $priceSummary->setFinalTotalPriceExcludingTax($priceSummary->final_total_price);

            $finalTotalPriceAfterTax = $this->calculateTotalPriceAfterTax($priceSummary->final_total_price, $priceInputData->tax_value);

            $priceSummary->setFinalTotalPrice($finalTotalPriceAfterTax);
        });
    }

    public static function addDiscountToPipe(ImmutableMultiYearDiscountData|ImmutablePrePayDiscountData|ImmutablePromotionalDiscountData|ImmutableSpecialNegotiationDiscountData|null $discount,
                                             array &$pipes): void
    {
        if (is_null($discount)) {
            return;
        }

        $pipe = match ($discount::class) {
            ImmutableMultiYearDiscountData::class => new ApplyMultiYearDiscountPipe($discount),
            ImmutablePrePayDiscountData::class => new ApplyPrePayDiscountPipe($discount),
            ImmutablePromotionalDiscountData::class => new ApplyPromotionalDiscountPipe($discount),
            ImmutableSpecialNegotiationDiscountData::class => new ApplySpecialNegotiationDiscountPipe($discount),
            default => throw DiscountException::unsupportedEntityType(),
        };

        array_push($pipes, $pipe);
    }

    public function calculatePackQuotePriceSummaryAfterCountryMarginTax(WorldwideQuote $quote, ImmutableMarginTaxData $marginTaxData): PackQuotePriceSummaryData
    {
        $quoteTotalPrice = $this->calculatePackQuoteTotalPrice($quote);

        $buyPrice = $this->calculatePackQuoteBuyPrice($quote);

        $totalPriceAfterMarginWithoutCustomDiscount = self::calculateTotalPriceAfterBottomUp(
            $quoteTotalPrice,
            $buyPrice,
            (float) $marginTaxData->margin_value,
        );

        $predefinedDiscounts = $this->predefinedQuoteDiscountsToApplicableDiscounts($quote);

        $priceSummaryAfterDiscounts = $this->calculatePriceSummaryAfterPredefinedDiscounts(
            QuotePriceInputData::immutable([
                'total_price' => $quoteTotalPrice,
                'buy_price' => $buyPrice,
                'margin_value' => (float) $marginTaxData->margin_value - (float) $quote->activeVersion->custom_discount,
                'tax_value' => (float) $quote->activeVersion->tax_value,
            ]),
            $predefinedDiscounts
        );

        $totalPriceAfterDiscounts = $priceSummaryAfterDiscounts->final_total_price_excluding_tax;

        $quoteMarginValue = $this->calculateMarginPercentage($totalPriceAfterDiscounts, $buyPrice);

        $finalTotalPrice = $this->calculateTotalPriceAfterTax($totalPriceAfterDiscounts, (float) $marginTaxData->tax_value);

        $quotePriceSummary = PriceSummaryData::immutable([
            'total_price' => $quoteTotalPrice,
            'total_price_after_margin' => $totalPriceAfterMarginWithoutCustomDiscount,
            'final_total_price' => $finalTotalPrice,
            'final_total_price_excluding_tax' => $totalPriceAfterDiscounts,
            'buy_price' => $buyPrice,
            'final_margin' => $quoteMarginValue,
        ]);

        return new PackQuotePriceSummaryData([
            'worldwide_quote_id' => $quote->getKey(),
            'quote_price_summary' => $quotePriceSummary,
        ]);
    }

    public function calculateContractQuoteFinalTotalPrice(WorldwideQuote $quote): QuoteFinalTotalPrice
    {
        return $quote->activeVersion->worldwideDistributions->reduce(function (QuoteFinalTotalPrice $quoteFinalTotalPrice, WorldwideDistribution $distribution) {
            if (is_null($distribution->total_price)) {
                $distribution->total_price = $this->distributionCalc->calculateTotalPriceOfDistributorQuote($distribution);
            }

            if (is_null($distribution->final_total_price) || is_null($distribution->applicable_discounts_value)) {
                $finalTotalPrice = $this->distributionCalc->calculateDistributionFinalTotalPrice($distribution, $distribution->total_price);

                $distribution->final_total_price = $finalTotalPrice->final_total_price_value;
                $distribution->applicable_discounts_value = $finalTotalPrice->applicable_discounts_value;
            }

            $quoteFinalTotalPrice->final_total_price_value += (float) $distribution->final_total_price;
            $quoteFinalTotalPrice->applicable_discounts_value += (float) $distribution->applicable_discounts_value;

            return $quoteFinalTotalPrice;
        }, new QuoteFinalTotalPrice());
    }
}
