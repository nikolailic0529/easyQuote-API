<?php

namespace App\Http\Resources\V1\WorldwideQuote;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetsGroup extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Models\WorldwideQuoteAssetsGroup|\App\Http\Resources\WorldwideQuote\AssetsGroup $this */

        return [
            'id' => $this->getKey(),
            'worldwide_quote_version_id' => $this->worldwide_quote_version_id,

            'group_name' => $this->group_name,
            'search_text' => $this->search_text,

            'assets' => PackAsset::collection($this->whenLoaded('assets', function () {

                /** @var \App\Models\WorldwideQuoteAssetsGroup|\App\Http\Resources\WorldwideQuote\AssetsGroup $this */

                return tap($this->assets, function (Collection $assets) {

                    /** @var \App\Models\WorldwideQuoteAsset[] $assets */

                    foreach ($assets as $asset) {

                        $asset->setAttribute('is_selected', true);

                    }

                });

            })),

            'assets_sum' => (float)$this->assets_sum_price,
            'assets_count' => $this->assets_count,

            'is_selected' => (bool)$this->is_selected,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
