<?php

namespace App\Domain\Geocoding\Integrations\AddressValidation\Models;

use App\Domain\Geocoding\Integrations\AddressValidation\Enum\RegionCodeEnum;

final class ValidateAddressRequestAddress
{
    /**
     * @param list<string> $addressLines
     */
    public function __construct(
        public readonly ?array $addressLines,
        public readonly ?RegionCodeEnum $regionCode = null,
        public readonly ?string $locality = null,
        public readonly ?string $administrativeArea = null,
        public readonly ?string $postalCode = null,
        public readonly ?string $languageCode = null,
    ) {
    }
}
