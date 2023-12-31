<?php

namespace App\Domain\Pipeliner\Services\Strategies\Contracts;

use App\Domain\SalesUnit\Models\SalesUnit;

interface SyncStrategy
{
    public function setSalesUnits(SalesUnit ...$units): static;

    public function getSalesUnits(): array;

    public function countPending(): int;

    public function iteratePending(): \Traversable;

    public function getModelType(): string;

    public function isApplicableTo(object $entity): bool;

    public function getByReference(string $reference): object;
}
