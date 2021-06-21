<?php

namespace App\DTO\Discounts;

use App\DTO\PriceSummaryData;
use Spatie\DataTransferObject\ImmutableDataTransferObject;

/**
 * Class ImmutablePriceSummaryData
 * @property-read PriceSummaryData $dataTransferObject
 *
 * @property-read float $total_price
 * @property-read float $total_price_after_margin
 * @property-read float $buy_price
 * @property-read float $country_margin
 * @property-read float $raw_margin
 * @property-read float $final_total_price
 * @property-read float $final_total_price_excluding_tax
 * @property-read float $final_margin
 * @property-read float $applicable_discounts_value
 * @property-read float|null $margin_after_multi_year_discount
 * @property-read float|null $margin_after_pre_pay_discount
 * @property-read float|null $margin_after_promotional_discount
 * @property-read float|null $margin_after_sn_discount
 * @property-read float|null $margin_after_country_margin_tax
 * @property-read float|null $margin_after_custom_discount
 */
final class ImmutablePriceSummaryData extends ImmutableDataTransferObject
{
    /**
     * ImmutableMarginAfterDiscountsData constructor.
     * @param PriceSummaryData $data
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
