<?php

namespace App\DTO\Opportunity;

use Spatie\DataTransferObject\DataTransferObject;

final class PipelineStageSummaryData extends DataTransferObject
{
    public string $stage_id;
    public int $total;
    public int $valid;
    public int $invalid;
    public float $base_amount;
}