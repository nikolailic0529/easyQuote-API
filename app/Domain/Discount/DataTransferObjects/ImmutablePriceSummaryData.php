<?php

namespace App\Domain\Discount\DataTransferObjects;

use App\Domain\Worldwide\DataTransferObjects\Calc\PriceSummaryData;
use Spatie\DataTransferObject\ImmutableDataTransferObject;

/**
 * Class ImmutablePriceSummaryData.
 *
 * @property \App\Domain\Worldwide\DataTransferObjects\Calc\PriceSummaryData $dataTransferObject
 * @property float                                                           $total_price
 * @property float                                                           $total_price_after_margin
 * @property float                                                           $buy_price
 * @property float                                                           $source_to_output_exchange_rate
 * @property float                                                           $country_margin
 * @property float                                                           $raw_margin
 * @property float                                                           $final_total_price
 * @property float                                                           $final_total_price_excluding_tax
 * @property float                                                           $final_margin
 * @property float                                                           $applicable_discounts_value
 * @property float|null                                                      $margin_after_multi_year_discount
 * @property float|null                                                      $margin_after_pre_pay_discount
 * @property float|null                                                      $margin_after_promotional_discount
 * @property float|null                                                      $margin_after_sn_discount
 * @property float|null                                                      $margin_after_country_margin_tax
 * @property float|null                                                      $margin_after_custom_discount
 */
final class ImmutablePriceSummaryData extends ImmutableDataTransferObject
{
    /**
     * ImmutableMarginAfterDiscountsData constructor.
     */
    public function __construct(PriceSummaryData $data)
    {
        parent::__construct($data);
    }

    public function setFinalTotalPrice(float $finalTotalPrice): void
    {
        $this->dataTransferObject->final_total_price = $finalTotalPrice;
    }

    public function setFinalTotalPriceExcludingTax(float $finalTotalPriceExcludingTax): void
    {
        $this->dataTransferObject->final_total_price_excluding_tax = $finalTotalPriceExcludingTax;
    }

    public function setMarginAfterMultiYearDiscount(?float $marginAfterMultiYearDiscount): void
    {
        $this->dataTransferObject->margin_after_multi_year_discount = $marginAfterMultiYearDiscount;
    }

    public function setMarginAfterPrePayDiscount(?float $marginAfterPrePayDiscount): void
    {
        $this->dataTransferObject->margin_after_pre_pay_discount = $marginAfterPrePayDiscount;
    }

    public function setMarginAfterPromotionalDiscount(?float $marginAfterPromotionalDiscount): void
    {
        $this->dataTransferObject->margin_after_promotional_discount = $marginAfterPromotionalDiscount;
    }

    public function setMarginAfterSnDiscount(?float $marginAfterSnDiscount): void
    {
        $this->dataTransferObject->margin_after_sn_discount = $marginAfterSnDiscount;
    }

    public function setApplicableDiscountsValue(float $applicableDiscountsValue): void
    {
        $this->dataTransferObject->applicable_discounts_value = $applicableDiscountsValue;
    }

    public function setFinalMargin(?float $finalMargin): void
    {
        $this->dataTransferObject->final_margin = $finalMargin;
    }
}
