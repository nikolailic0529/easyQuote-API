<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObject;

final class AssetServiceLookupResult extends DataTransferObject
{
    public string $asset_id;

    public ?int $index = null;

    public string $vendor_short_code;

    public ?string $serial_no;

    public ?string $model;

    public ?string $type;

    public ?string $sku;

    public ?string $service_sku;

    public ?string $product_name;

    public ?string $expiry_date;

    public ?string $country_code;

    public ?string $currency_code;

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Quote\AssetServiceLevel[]
     */
    public array $service_levels;
}
