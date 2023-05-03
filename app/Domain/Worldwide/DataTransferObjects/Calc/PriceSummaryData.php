<?php

namespace App\Domain\Worldwide\DataTransferObjects\Calc;

use App\Domain\Discount\DataTransferObjects\ImmutablePriceSummaryData;
use Spatie\DataTransferObject\DataTransferObject;

final class PriceSummaryData extends DataTransferObject
{
    public float $total_price;

    public float $total_price_after_margin;

    public float $buy_price;

    public float $source_to_output_exchange_rate = 1.0;

    public float $final_total_price = 0.0;

    public float $final_total_price_excluding_tax = 0.0;

    public float $applicable_discounts_value = 0.0;

    public ?float $raw_margin = null;

    public ?float $final_margin = null;

    public ?float $margin_after_custom_discount = null;

    public ?float $margin_after_multi_year_discount = null;

    public ?float $margin_after_pre_pay_discount = null;

    public ?float $margin_after_promotional_discount = null;

    public ?float $margin_after_sn_discount = null;

    public static function immutable(array $parameters = []): ImmutablePriceSummaryData
    {
        return new ImmutablePriceSummaryData(new PriceSummaryData($parameters));
    }
}
