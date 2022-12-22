<?php

namespace App\Services\Company\DataEnrichment\Models;

use App\Services\Company\DataEnrichment\Enum\CompanyStatusEnum;

final class CompanyProfile
{
    public function __construct(
        public readonly string $registeredNumber,
        public readonly string $name,
        public readonly int $employeesNumber,
        public readonly \DateTimeInterface $creationDate,
        public readonly array $sicCodes,
        public readonly CompanyStatusEnum $status,
        public readonly CompanyAddress $address,
    ) {
    }
}