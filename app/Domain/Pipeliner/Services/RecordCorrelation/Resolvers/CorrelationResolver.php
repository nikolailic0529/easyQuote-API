<?php

namespace App\Domain\Pipeliner\Services\RecordCorrelation\Resolvers;

interface CorrelationResolver
{
    public function __invoke(array $item, array $another): bool;

    public function canResolveFor(string $strategy): bool;
}
