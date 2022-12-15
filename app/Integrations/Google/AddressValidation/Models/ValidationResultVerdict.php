<?php

namespace App\Integrations\Google\AddressValidation\Models;

final class ValidationResultVerdict
{
    public function __construct(
        public readonly string $inputGranularity,
        public readonly string $validationGranularity,
        public readonly string $geocodeGranularity,
        public readonly ?bool $addressComplete,
        public readonly ?bool $hasUnconfirmedComponents,
        public readonly ?bool $hasInferredComponents,
        public readonly ?bool $hasReplacedComponents,
    ) {
    }
}