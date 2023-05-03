<?php

namespace App\Domain\Worldwide\DataTransferObjects\Opportunity;

use Spatie\DataTransferObject\DataTransferObject;

final class BatchSaveOpportunitiesData extends DataTransferObject
{
    /**
     * @var \App\Domain\Worldwide\Models\Opportunity[]
     */
    public array $opportunities;
}
