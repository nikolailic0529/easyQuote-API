<?php

namespace App\Http\Resources\V1\Asset;

use App\Models\Asset;
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetWithIncludes extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var Asset|AssetWithIncludes $this */

        return [
            'id' => $this->id,

            'asset_category_id' => $this->asset_category_id,
            'user_id' => $this->user_id,
            'address_id' => $this->address_id,
            'vendor_id' => $this->vendor_id,
            'quote_id' => $this->quote_id,
            'quote_type' => $this->quote_type,

            'rfq_number' => value(function () {
                /** @var Asset|AssetWithIncludes $this */

                if (is_null($this->quote)) {
                    return null;
                }

                return match($this->quote::class) {
                    Quote::class => $this->quote->customer->rfq,
                    WorldwideQuote::class => $this->quote->quote_number,
                };
            }),

            'vendor_short_code' => $this->vendor_short_code,
            'asset_category_name' => $this->assetCategory->name,
            'address' => $this->address,

            'country' => $this->country,

            'unit_price' => $this->unit_price,
            'base_warranty_start_date' => optional($this->base_warranty_start_date)->format(config('date.format')),
            'base_warranty_end_date' => optional($this->base_warranty_end_date)->format(config('date.format')),
            'active_warranty_start_date' => optional($this->active_warranty_start_date)->format(config('date.format')),
            'active_warranty_end_date' => optional($this->active_warranty_end_date)->format(config('date.format')),
            'product_number' => $this->product_number,
            'serial_number' => $this->serial_number,
            'product_description' => $this->product_description,
            'product_image' => $this->product_image,
            'created_at' => optional($this->created_at)->format(config('date.format_time')),
            'updated_at' => optional($this->updated_at)->format(config('date.format_time')),
        ];
    }
}
