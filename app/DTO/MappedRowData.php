<?php

namespace App\DTO;

use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;

class MappedRowData extends DataTransferObject
{
    public ?string $product_no;

    public ?string $service_sku;

    public ?string $description;

    public ?string $serial_no;

    public ?Carbon $date_from;

    public ?Carbon $date_to;

    public int $qty;

    public float $price;

    public float $original_price;

    public ?string $pricing_document;

    public ?string $system_handle;

    public ?string $searchable;

    public ?string $service_level_description;
}
