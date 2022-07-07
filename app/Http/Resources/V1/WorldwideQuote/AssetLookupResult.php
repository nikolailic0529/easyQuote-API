<?php

namespace App\Http\Resources\V1\WorldwideQuote;

use App\Services\WorldwideQuote\WorldwideQuoteDataMapper;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetLookupResult extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Models\WorldwideQuoteAsset|\App\Http\Resources\WorldwideQuote\AssetLookupResult $this */

        return parent::toArray($request) + [
                'machine_address_string' => $this->whenLoaded('machineAddress', function () {
                    /** @var \App\Models\WorldwideQuoteAsset|\App\Http\Resources\WorldwideQuote\AssetLookupResult $this */

                    return WorldwideQuoteDataMapper::formatAddressToString($this->machineAddress);
                }),
                'buy_currency_code' => $this->whenLoaded('buyCurrency', function () {
                    /** @var \App\Models\WorldwideQuoteAsset|\App\Http\Resources\WorldwideQuote\AssetLookupResult $this */

                    return $this->buyCurrency->code;
                })
            ];
    }
}
