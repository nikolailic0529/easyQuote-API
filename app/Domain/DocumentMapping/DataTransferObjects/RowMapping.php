<?php

namespace App\Domain\DocumentMapping\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;

class RowMapping extends DataTransferObject
{
    public ?string $product_no;

    public ?string $service_sku;

    public ?string $description;

    public ?string $serial_no;

    public ?string $date_from;

    public ?string $date_to;

    public ?string $qty;

    public ?string $price;

    public ?string $pricing_document;

    public ?string $system_handle;

    public ?string $searchable;

    public ?string $service_level_description;
}
