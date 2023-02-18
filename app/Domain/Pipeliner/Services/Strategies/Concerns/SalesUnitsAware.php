<?php

namespace App\Domain\Pipeliner\Services\Strategies\Concerns;

use App\Domain\SalesUnit\Models\SalesUnit;

trait SalesUnitsAware
{
    /** @var list<SalesUnit> */
    protected array $salesUnits = [];

    public function setSalesUnits(SalesUnit ...$units): static
    {
        return tap($this, fn () => $this->salesUnits = $units);
    }

    public function getSalesUnits(): array
    {
        return $this->salesUnits;
    }
}
