<?php

namespace App\Domain\Pipeliner\Services\RecordCorrelation\Resolvers;

use App\Domain\Pipeliner\Services\RecordCorrelation\Concerns\AssertsAttributes;

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
