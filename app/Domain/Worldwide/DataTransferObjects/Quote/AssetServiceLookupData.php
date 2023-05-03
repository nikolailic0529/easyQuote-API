<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class AssetServiceLookupData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public string $asset_id;

    public ?int $asset_index = null;

    /**
     * @Constraints\Choice({"HPE", "LEN"})
     */
    public string $vendor_short_code;

    /**
     * @Constraints\NotBlank
     */
    public string $serial_no;

    /**
     * @Constraints\NotBlank(allowNull=true)
     */
    public ?string $sku = null;

    /**
     * @Constraints\NotBlank
     */
    public string $country_code;

    /**
     * @Constraints\Currency()
     */
    public ?string $currency_code = null;
}
