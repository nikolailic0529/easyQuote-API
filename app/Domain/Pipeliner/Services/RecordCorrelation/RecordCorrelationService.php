<?php

namespace App\Domain\Pipeliner\Services\RecordCorrelation;

use App\Domain\Pipeliner\Services\RecordCorrelation\Resolvers\CompanyCorrelationResolver;
use App\Domain\Pipeliner\Services\RecordCorrelation\Resolvers\CorrelationResolver;
use App\Domain\Pipeliner\Services\RecordCorrelation\Resolvers\GenericCorrelationResolver;
use App\Domain\Pipeliner\Services\RecordCorrelation\Resolvers\OpportunityCorrelationResolver;

class RecordCorrelationService
{
    /** @var list<CorrelationResolver> */
    protected array $pipeline;

    public function __construct(
        GenericCorrelationResolver $genericCorrelationResolver,
        OpportunityCorrelationResolver $opportunityCorrelationResolver,
        CompanyCorrelationResolver $companyCorrelationResolver,
    ) {
        $this->pipeline[] = $genericCorrelationResolver;
        $this->pipeline[] = $opportunityCorrelationResolver;
        $this->pipeline[] = $companyCorrelationResolver;
    }

    public function matches(string $strategy, array $item, array $another): bool
    {
        foreach ($this->pipeline as $resolver) {
            if (!$resolver->canResolveFor($strategy)) {
                continue;
            }

            if ($resolver($item, $another)) {
                return true;
            }
        }

        return false;
    }
}
