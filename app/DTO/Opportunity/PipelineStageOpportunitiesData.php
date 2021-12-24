<?php

namespace App\DTO\Opportunity;

use App\Models\Opportunity;
use Spatie\DataTransferObject\DataTransferObject;

final class PipelineStageOpportunitiesData extends DataTransferObject
{
    public string $stage_id;

    public string $stage_name;

    public int $stage_order;

    /**
     * @var App\Models\Opportunity[]
     */
    public array $opportunities;
}