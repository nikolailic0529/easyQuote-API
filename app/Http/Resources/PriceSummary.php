<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PriceSummary extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\DTO\PriceSummaryData|PriceSummary $this */

        return [
            'total_price' => $this->toDecimal($this->total_price),
            'buy_price' => $this->toDecimal($this->buy_price),
            'final_total_price' => $this->toDecimal($this->final_total_price),
            'final_total_price_excluding_tax' => $this->toDecimal($this->final_total_price_excluding_tax),
            'applicable_discounts_value' => $this->toDecimal($this->applicable_discounts_value),
            'margin_after_custom_discount' => $this->toDecimal($this->margin_after_custom_discount),
            'margin_after_multi_year_discount' => $this->toDecimal($this->margin_after_multi_year_discount),
            'margin_after_pre_pay_discount' => $this->toDecimal($this->margin_after_pre_pay_discount),
            'margin_after_promotional_discount' => $this->toDecimal($this->margin_after_promotional_discount),
            'margin_after_sn_discount' => $this->toDecimal($this->margin_after_sn_discount),
            'final_margin' => $this->toDecimal($this->final_margin),
        ];
    }

    private function toDecimal(?float $value)
    {
        return transform($value, function (float $value) {
            return number_format($value, '2', '.', '');
        });
    }
}
