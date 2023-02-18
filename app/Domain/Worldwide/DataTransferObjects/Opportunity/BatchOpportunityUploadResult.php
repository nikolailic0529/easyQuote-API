<?php

namespace App\Domain\Worldwide\DataTransferObjects\Opportunity;

use Spatie\LaravelData\Data;

final class BatchOpportunityUploadResult extends Data
{
    public function __construct(
        public readonly array $opportunities,
        public readonly array $errors,
    ) {
    }
}
