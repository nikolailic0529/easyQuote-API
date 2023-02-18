<?php

namespace App\Domain\Asset\DataTransferObjects;

use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;

class QuoteAsset extends DataTransferObject
{
    public string $quote_id;

    public string $quote_type;

    public ?string $company_reference_id;

    public string $vendor_id;

    public string $user_id;

    public string $asset_category_id;

    public string $vendor_short_code;

    public ?string $product_number;

    public ?string $serial_number;

    public ?string $address_id;

    public ?string $service_description;

    public ?string $product_description;

    public ?string $pricing_document;

    public ?string $system_handle;

    public ?string $service_agreement_id;

    public ?string $product_image;

    public ?Carbon $base_warranty_start_date;

    public ?Carbon $base_warranty_end_date;

    public ?Carbon $active_warranty_start_date;

    public ?Carbon $active_warranty_end_date;

    public int $quantity = 1;

    public float $unit_price = 0;
}
