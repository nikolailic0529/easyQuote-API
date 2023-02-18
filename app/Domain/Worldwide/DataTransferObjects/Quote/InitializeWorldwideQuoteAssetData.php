<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class InitializeWorldwideQuoteAssetData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public ?string $vendor_id;

    /**
     * @Constraints\Uuid
     */
    public ?string $buy_currency_id;

    /**
     * @Constraints\Uuid
     */
    public ?string $machine_address_id;

    /**
     * @Constraints\Country()
     */
    public ?string $country_code;

    public ?string $serial_no;

    public ?string $sku;

    public ?string $service_sku;

    public ?string $product_name;

    public ?Carbon $expiry_date;

    public ?string $service_level_description;

    public ?float $buy_price;

    public ?float $buy_price_margin;

    public ?float $price;

    public ?float $original_price;

    public ?float $exchange_rate_margin;

    public ?float $exchange_rate_value;

    public bool $is_warranty_checked = false;
}
