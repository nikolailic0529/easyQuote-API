<?php

namespace App\DTO;

use Spatie\DataTransferObject\DataTransferObject;
use App\Models\Address;
use App\Models\AssetCategory;
use App\Models\Quote\BaseQuote;
use Carbon\Carbon;

class QuoteAsset extends DataTransferObject
{
    public string $vendor_id, $user_id, $vendor_short_code, $asset_category_id, $quote_id;

    public ?string $product_number, $serial_number, $address_id, $service_description, $product_description, $pricing_document, $system_handle, $service_agreement_id;

    public Carbon $base_warranty_start_date, $base_warranty_end_date, $active_warranty_start_date, $active_warranty_end_date;

    public int $quantity = 1;

    public float $unit_price = 0;

    public bool $is_migrated = true;

    public static function create(object $row, BaseQuote $quote, ?AssetCategory $assetCategory, ?Address $address)
    {
        $user_id = $quote->user_id;
        $vendor_id = $quote->vendor_id;
        $vendor_short_code = $quote->vendor->short_code;
        $quote_id = $quote->getKey();
        $address_id = optional($address)->getKey();
        $asset_category_id = optional($assetCategory)->getKey();

        $product_number = optional($row)->product_no;

        $product_description = optional($row)->description;
        $service_description = optional($row)->service_level_description;
        $serial_number = optional($row)->serial_no;
        $pricing_document = optional($row)->pricing_document;
        $system_handle = optional($row)->system_handle;
        $service_agreement_id = optional($row)->searchable;

        $base_warranty_start_date = $active_warranty_start_date = optional($row->date_from, fn ($date) => Carbon::createFromFormat('d/m/Y', $date)->startOfDay());
        $base_warranty_end_date = $active_warranty_end_date = optional($row->date_to, fn ($date) => Carbon::createFromFormat('d/m/Y', $date)->startOfDay());

        $unit_price = (float) optional($row)->price / $quote->margin_divider * $quote->base_exchange_rate;

        return new static(compact(
            'user_id',
            'address_id',
            'vendor_id',
            'quote_id',
            'vendor_short_code',
            'asset_category_id',
            'product_number',
            'product_description',
            'service_description',
            'serial_number',
            'pricing_document',
            'system_handle',
            'service_agreement_id',
            'base_warranty_start_date',
            'base_warranty_end_date',
            'active_warranty_start_date',
            'active_warranty_end_date',
            'unit_price',
        ));
    }
}
