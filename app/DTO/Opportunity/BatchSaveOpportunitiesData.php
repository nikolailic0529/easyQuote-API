<?php

namespace App\DTO\Opportunity;

use Spatie\DataTransferObject\DataTransferObject;

final class BatchSaveOpportunitiesData extends DataTransferObject
{
    /**
     * @var \App\Models\Opportunity[]
     */
    public array $opportunities;
}
