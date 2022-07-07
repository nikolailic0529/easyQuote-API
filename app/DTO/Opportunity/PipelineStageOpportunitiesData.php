<?php

namespace App\DTO\Opportunity;

use Spatie\DataTransferObject\DataTransferObject;

final class PipelineStageOpportunitiesData extends DataTransferObject
{
    public string $stage_id;

    public string $stage_name;

    public int $stage_order;

    public float $stage_percentage;

    public PipelineStageSummaryData $summary;

    public array $meta = [];

    /**
     * @var \App\Models\Opportunity[]
     */
    public array $opportunities;
}