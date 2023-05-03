<?php

namespace App\Domain\Pipeliner\Services\RecordCorrelation\Resolvers;

use App\Domain\Pipeliner\Services\RecordCorrelation\Concerns\AssertsAttributes;
use App\Domain\Pipeliner\Services\Strategies\PullOpportunityStrategy;
use App\Domain\Pipeliner\Services\Strategies\PushOpportunityStrategy;

final class OpportunityCorrelationResolver implements CorrelationResolver
{
    use AssertsAttributes;

    public function __invoke(array $item, array $another): bool
    {
        $this->assertAttributePresent('name', $item, $another);
        $this->assertAttributePresent('unit_name', $item, $another);

        if (mb_strtolower($item['name']) !== mb_strtolower($another['name'])) {
            return false;
        }

        if ($item['unit_name'] !== $another['unit_name']) {
            return false;
        }

        return true;
    }

    public function canResolveFor(string $strategy): bool
    {
        if (is_a($strategy, PullOpportunityStrategy::class, true)) {
            return true;
        }

        if (is_a($strategy, PushOpportunityStrategy::class, true)) {
            return true;
        }

        return false;
    }
}
