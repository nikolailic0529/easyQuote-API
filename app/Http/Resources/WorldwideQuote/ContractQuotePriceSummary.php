<?php

namespace App\Http\Resources\WorldwideQuote;

use App\DTO\WorldwideQuote\ContractQuotePriceSummaryData;
use App\DTO\WorldwideQuote\DistributorQuotePriceSummary;
use App\Http\Resources\PriceSummary;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractQuotePriceSummary extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var ContractQuotePriceSummaryData|ContractQuotePriceSummary $this */

        return [
            'worldwide_quote_id' => $this->worldwide_quote_id,
            'price_summary' => PriceSummary::make($this->quote_price_summary),
            'worldwide_distributions' => array_map(function (DistributorQuotePriceSummary $quotePriceSummary) {
                return [
                    'worldwide_distribution_id' => $quotePriceSummary->worldwide_distribution_id,
                    'index' => $quotePriceSummary->index,
                    'price_summary' => PriceSummary::make($quotePriceSummary->price_summary)
                ];

            }, $this->worldwide_distributions),

        ];
    }
}
