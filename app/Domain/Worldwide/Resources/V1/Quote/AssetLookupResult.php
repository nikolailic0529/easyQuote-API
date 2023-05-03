<?php

namespace App\Domain\Worldwide\Resources\V1\Quote;

use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteDataMapper;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetLookupResult extends JsonResource
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
        /* @var \App\Domain\Worldwide\Models\WorldwideQuoteAsset|\App\Http\Resources\WorldwideQuote\AssetLookupResult $this */

        return parent::toArray($request) + [
                'machine_address_string' => $this->whenLoaded('machineAddress', function () {
                    /* @var \App\Domain\Worldwide\Models\WorldwideQuoteAsset|\App\Http\Resources\WorldwideQuote\AssetLookupResult $this */

                    return WorldwideQuoteDataMapper::formatAddressToString($this->machineAddress);
                }),
                'buy_currency_code' => $this->whenLoaded('buyCurrency', function () {
                    /* @var \App\Domain\Worldwide\Models\WorldwideQuoteAsset|\App\Http\Resources\WorldwideQuote\AssetLookupResult $this */

                    return $this->buyCurrency->code;
                }),
            ];
    }
}
