<?php

namespace App\DTO;

use App\Models\Asset;
use Spatie\DataTransferObject\DataTransferObject;

class AssetData extends DataTransferObject
{
    public string $id, $asset_category_name, $vendor_id, $vendor_name, $vendor_short_code, $product_number, $serial_number;

    public ?string $item_number, $product_description, $base_warranty_start_date, $base_warranty_end_date, $active_warranty_start_date, $active_warranty_end_date;

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
            'active_warranty_end_date' => optional($asset->active_warranty_end_date)->format(config('date.format'))
        ]);
    }
}