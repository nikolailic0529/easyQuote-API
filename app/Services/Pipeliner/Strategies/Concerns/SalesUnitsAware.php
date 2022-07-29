<?php

namespace App\Services\Pipeliner\Strategies\Concerns;

use App\Models\SalesUnit;

trait SalesUnitsAware
{
    protected array $salesUnits = [];

    public function setSalesUnits(SalesUnit ...$units): static
    {
        return tap($this, fn() => $this->salesUnits = $units);
    }

    public function getSalesUnits(): array
    {
        return $this->salesUnits;
    }
}