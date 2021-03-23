<?php

namespace App\DTO\WorldwideQuote\Export;

use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;

final class AssetData extends DataTransferObject
{
    public string $vendor_short_code;

    public string $product_no;

    public string $service_sku;

    public string $description;

    public string $serial_no;

    public string $date_from;

    public string $date_to;

    public int $qty;

    public string $price;

    public float $price_float;

    public string $pricing_document;

    public string $system_handle;

    public string $searchable;

    public string $service_level_description;

    public string $machine_address_string;
}
