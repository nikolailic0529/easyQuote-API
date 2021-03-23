<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class AssetServiceLookupData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $asset_id;


    public ?int $asset_index = null;

    /**
     * @Constraints\Choice({"HPE", "LEN"})
     *
     * @var string
     */
    public string $vendor_short_code;

    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $serial_no;

    /**
     * @Constraints\NotBlank(allowNull=true)
     *
     * @var string|null
     */
    public ?string $sku = null;

    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $country_code;

    /**
     * @Constraints\Currency()
     *
     * @var string|null
     */
    public ?string $currency_code = null;
}
