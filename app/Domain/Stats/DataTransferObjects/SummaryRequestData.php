<?php

namespace App\Domain\Stats\DataTransferObjects;

use Carbon\CarbonPeriod;

final class SummaryRequestData
{
    public function __construct(
        public readonly string $userId,
        public readonly ?CarbonPeriod $period = null,
        public readonly ?string $countryId = null,
        public readonly ?string $currencyId = null,
    ) {
    }
}
