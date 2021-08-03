<?php

namespace App\DTO;

use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;

class QuoteAsset extends DataTransferObject
{
    public string $quote_id;

    public string $quote_type;

    public ?string $company_reference_id;

    public string $vendor_id, $user_id, $vendor_short_code, $asset_category_id;

    public ?string $product_number, $serial_number, $address_id, $service_description, $product_description, $pricing_document, $system_handle, $service_agreement_id;

    public ?Carbon $base_warranty_start_date, $base_warranty_end_date, $active_warranty_start_date, $active_warranty_end_date;

    public int $quantity = 1;

    public float $unit_price = 0;
}
