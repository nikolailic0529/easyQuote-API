<?php

namespace App\DTO\Opportunity;

use Spatie\DataTransferObject\DataTransferObject;

final class PipelineStageSummaryData extends DataTransferObject
{
    public int $total;

    public int $valid;

    public int $invalid;

    public float $base_amount;
}