<?php

namespace App\Domain\Asset\Resources\V1;

use App\Domain\Asset\Models\Asset;
use App\Domain\Rescue\Models\Quote;
use App\Domain\User\Resources\V1\UserRelationResource;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Asset
 */
class AssetWithIncludes extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        $user = $request->user();

        return [
            'id' => $this->getKey(),

            'permissions' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],

            'asset_category_id' => $this->assetCategory()->getParentKey(),
            'user_id' => $this->user()->getParentKey(),
            'address_id' => $this->address()->getParentKey(),
            'vendor_id' => $this->vendor()->getParentKey(),
            'quote_id' => $this->quote()->getParentKey(),
            'quote_type' => $this->quote_type,

            'user' => UserRelationResource::make($this->user),
            'sharing_users' => UserRelationResource::collection($this->sharingUsers),

            'rfq_number' => value(function () {
                /** @var Asset|AssetWithIncludes $this */
                if (is_null($this->quote)) {
                    return null;
                }

                return match ($this->quote::class) {
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
            'created_at' => $this->created_at?->format(config('date.format_time')),
            'updated_at' => $this->updated_at?->format(config('date.format_time')),
        ];
    }
}
