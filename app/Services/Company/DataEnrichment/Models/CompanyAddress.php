<?php

namespace App\Services\Company\DataEnrichment\Models;

final class CompanyAddress
{
    public function __construct(
        public readonly ?string $locality,
        public readonly ?string $postCode,
        public readonly ?string $address1,
        public readonly ?string $country,
    ) {
    }
}