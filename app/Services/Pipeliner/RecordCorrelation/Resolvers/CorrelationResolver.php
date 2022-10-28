<?php

namespace App\Services\Pipeliner\RecordCorrelation\Resolvers;

use App\Services\Pipeliner\Strategies\Contracts\SyncStrategy;

interface CorrelationResolver
{
    public function __invoke(array $item, array $another): bool;

    public function canResolveFor(string $strategy): bool;
}