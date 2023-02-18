<?php

namespace App\Domain\Geocoding\Integrations\AddressValidation\Models;

use App\Domain\Geocoding\Integrations\AddressValidation\Enum\RegionCodeEnum;

final class ValidationResultAddressPostalAddress
{
    public function __construct(
        public readonly ?int $revision,
        public readonly ?RegionCodeEnum $regionCode,
        public readonly ?string $languageCode,
        public readonly ?string $postalCode,
        public readonly ?string $sortingCode,
        public readonly ?string $administrativeArea,
        public readonly ?string $locality,
        public readonly ?string $sublocality,
        public readonly ?array $addressLines,
        public readonly ?array $recipients,
        public readonly ?string $organization,
    ) {
    }
}
