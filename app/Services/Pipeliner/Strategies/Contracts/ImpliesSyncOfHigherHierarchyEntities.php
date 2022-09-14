<?php

namespace App\Services\Pipeliner\Strategies\Contracts;

interface ImpliesSyncOfHigherHierarchyEntities
{
    public function resolveHigherHierarchyEntities(mixed $entity): iterable;
}