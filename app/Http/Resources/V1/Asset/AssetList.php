<?php

namespace App\Http\Resources\V1\Asset;

use Illuminate\Http\Resources\Json\JsonResource;

class AssetList extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Models\Asset|\App\Http\Resources\Asset\AssetList $this */

        return [
            'id' => $this->getKey(),

            'asset_category_id' => $this->asset_category_id,
            'user_id' => $this->user_id,
            'address_id' => $this->address_id,
            'vendor_id' => $this->vendor_id,
            'quote_id' => $this->quote_id,

            'customer_name' => $this->customer_name,
            'rfq_number' => $this->customer_rfq_number,

            'vendor_short_code' => $this->vendor_short_code,
            'asset_category_name' => $this->asset_category_name,

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
