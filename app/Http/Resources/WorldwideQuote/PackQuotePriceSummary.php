<?php

namespace App\Http\Resources\WorldwideQuote;

use App\DTO\Discounts\ImmutablePriceSummaryData;
use App\DTO\WorldwideQuote\PackQuotePriceSummaryData;
use App\Http\Resources\PriceSummary;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Http\Resources\Json\JsonResource;

class PackQuotePriceSummary extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var PackQuotePriceSummaryData|PackQuotePriceSummary $this */

        return [
            'worldwide_quote_id' => $this->worldwide_quote_id,
            'price_summary' => PriceSummary::make($this->quote_price_summary)
        ];
    }
}
