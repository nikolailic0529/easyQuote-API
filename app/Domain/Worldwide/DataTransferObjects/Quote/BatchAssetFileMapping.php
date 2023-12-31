<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObject;

final class BatchAssetFileMapping extends DataTransferObject
{
    public ?string $serial_no;

    public ?string $sku;

    public ?string $service_sku;

    public ?string $product_name;

    public ?string $expiry_date;

    public ?string $service_level_description;

    public ?string $selling_price;

    public ?string $price;

    public ?string $buy_price_value;

    public ?string $buy_price_currency;

    public ?string $buy_price_margin;

    public ?string $exchange_rate_value;

    public ?string $exchange_rate_margin;

    public ?string $vendor;

    public ?string $country;

    public ?string $street_address;

    public ?string $post_code;

    public ?string $city;

    public ?string $state;

    public ?string $state_code;
}
