<?php

namespace App\Domain\Asset\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class AssetOfCompany extends JsonResource
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
        /** @var \App\Domain\Asset\Models\Asset|AssetOfCompany $this */

        /** @var \App\Domain\User\Models\User $user */
        $user = $request->user();

        return [
            'id' => $this->getKey(),
            'user_fullname' => $this->user_fullname,

            'product_number' => $this->product_number,
            'serial_number' => $this->serial_number,
            'product_image' => $this->product_image,

            'vendor_short_code' => $this->vendor_short_code,
            'asset_category_name' => $this->asset_category_name,
            'base_warranty_start_date' => $this->base_warranty_start_date?->format('Y-m-d'),
            'base_warranty_end_date' => $this->base_warranty_end_date?->format('Y-m-d'),
            'active_warranty_start_date' => $this->active_warranty_start_date?->format('Y-m-d'),
            'active_warranty_end_date' => $this->active_warranty_end_date?->format('Y-m-d'),

            'permissions' => [
                'view' => $user?->can('view', $this->resource),
                'update' => $user?->can('update', $this->resource),
                'delete' => $user?->can('delete', $this->resource),
            ],

            'created_at' => $this->{$this->getCreatedAtColumn()},
        ];
    }
}
