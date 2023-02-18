<?php

namespace App\Domain\Pipeliner\Services\RecordCorrelation\Resolvers;

use App\Domain\Pipeliner\Services\RecordCorrelation\Concerns\AssertsAttributes;
use App\Domain\Pipeliner\Services\Strategies\PullCompanyStrategy;
use App\Domain\Pipeliner\Services\Strategies\PushCompanyStrategy;

final class CompanyCorrelationResolver implements CorrelationResolver
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
        if (is_a($strategy, PullCompanyStrategy::class, true)) {
            return true;
        }

        if (is_a($strategy, PushCompanyStrategy::class, true)) {
            return true;
        }

        return false;
    }
}
