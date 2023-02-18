<?php

namespace App\Domain\Pipeliner\Services\Strategies\Contracts;

interface ImpliesSyncOfHigherHierarchyEntities
{
    public function resolveHigherHierarchyEntities(mixed $entity): iterable;
}
