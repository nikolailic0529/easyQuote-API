<?php

namespace App\DTO\Asset;

use Spatie\DataTransferObject\DataTransferObject;

final class GenericAssetData extends DataTransferObject
{
    public string $vendor_reference;

    public string $serial_no;

    public string $sku;

    public ?string $product_description;
}