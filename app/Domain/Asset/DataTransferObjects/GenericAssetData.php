<?php

namespace App\Domain\Asset\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;

final class GenericAssetData extends DataTransferObject
{
    public string $vendor_reference;

    public string $serial_no;

    public string $sku;

    public ?string $product_description;
}
