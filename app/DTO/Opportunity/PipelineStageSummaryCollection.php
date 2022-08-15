<?php

namespace App\DTO\Opportunity;

use Illuminate\Support\Collection;

class PipelineStageSummaryCollection extends Collection
{
    public function offsetGet($key): PipelineStageSummaryData
    {
        return parent::offsetGet($key);
    }
}