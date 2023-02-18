<?php

namespace App\Domain\Worldwide\Resources\V1\Quote;

use App\Domain\Worldwide\DataTransferObjects\Quote\PackQuotePriceSummaryData;
use Illuminate\Http\Resources\Json\JsonResource;

class PackQuotePriceSummary extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        /* @var PackQuotePriceSummaryData|PackQuotePriceSummary $this */

        return [
            'worldwide_quote_id' => $this->worldwide_quote_id,
            'price_summary' => PriceSummary::make($this->quote_price_summary),
        ];
    }
}
