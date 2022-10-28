<?php

namespace App\Services\Pipeliner\RecordCorrelation\Resolvers;

use App\Services\Pipeliner\RecordCorrelation\Concerns\AssertsAttributes;
use App\Services\Pipeliner\Strategies\Contracts\SyncStrategy;

final class GenericCorrelationResolver implements CorrelationResolver
{
    use AssertsAttributes;

    public function __invoke(array $item, array $another): bool
    {
        $this->assertAttributePresent('pl_reference', $item, $another);

        return $item['pl_reference'] === $another['pl_reference'];
    }

    public function canResolveFor(string $strategy): bool
    {
        return true;
    }
}