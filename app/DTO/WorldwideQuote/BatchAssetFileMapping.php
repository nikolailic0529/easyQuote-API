<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObject;

final class BatchAssetFileMapping extends DataTransferObject
{
    public ?string $serial_no;

    public ?string $sku;

    public ?string $service_sku;

    public ?string $product_name;

    public ?string $expiry_date;

    public ?string $service_level_description;

    public ?string $price;

    public ?string $vendor;

    public ?string $country;

    public ?string $street_address;

    public ?string $post_code;

    public ?string $city;

    public ?string $state;

    public ?string $state_code;
}
