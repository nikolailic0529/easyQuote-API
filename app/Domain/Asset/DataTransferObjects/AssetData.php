<?php

namespace App\Domain\Asset\DataTransferObjects;

use App\Domain\Asset\Models\Asset;
use Spatie\DataTransferObject\DataTransferObject;

class AssetData extends DataTransferObject
{
    public string $id;
    public string $asset_category_name;
    public string $vendor_id;
    public string $vendor_name;
    public string $vendor_short_code;
    public string $product_number;
    public string $serial_number;

    public ?string $item_number;
    public ?string $product_description;
    public ?string $base_warranty_start_date;
    public ?string $base_warranty_end_date;
    public ?string $active_warranty_start_date;
    public ?string $active_warranty_end_date;

    public float $unit_price;

    public static function create(Asset $asset)
    {
        return new static([
            'id' => $asset->id,

            'asset_category_name' => $asset->assetCategory->name,

            'vendor_id' => $asset->vendor_id,
            'vendor_name' => $asset->vendor->name,
            'vendor_short_code' => $asset->vendor->short_code,

            'product_number' => $asset->product_number,
            'serial_number' => $asset->serial_number,
            'item_number' => $asset->item_number,
            'product_description' => $asset->product_description,

            'unit_price' => (float) $asset->unit_price,
            'base_warranty_start_date' => optional($asset->base_warranty_start_date)->format(config('date.format')),
            'base_warranty_end_date' => optional($asset->base_warranty_end_date)->format(config('date.format')),
            'active_warranty_start_date' => optional($asset->active_warranty_start_date)->format(config('date.format')),
            'active_warranty_end_date' => optional($asset->active_warranty_end_date)->format(config('date.format')),
        ]);
    }
}
