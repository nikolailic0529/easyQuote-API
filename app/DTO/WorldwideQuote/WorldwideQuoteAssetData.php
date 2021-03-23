<?php

namespace App\DTO\WorldwideQuote;

use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class WorldwideQuoteAssetData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $id;

    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $vendor_id;

    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $machine_address_id;

    /**
     * @Constraints\Country()
     *
     * @var string|null
     */
    public ?string $country_code;

    public ?string $serial_no;

    public ?string $sku;

    public ?string $service_sku;

    public ?string $product_name;

    public ?Carbon $expiry_date;

    public ?string $service_level_description;

    public ?float $price;
}
