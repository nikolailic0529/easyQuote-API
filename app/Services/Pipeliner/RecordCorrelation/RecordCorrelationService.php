<?php

namespace App\Services\Pipeliner\RecordCorrelation;

use App\Services\Pipeliner\RecordCorrelation\Resolvers\CompanyCorrelationResolver;
use App\Services\Pipeliner\RecordCorrelation\Resolvers\CorrelationResolver;
use App\Services\Pipeliner\RecordCorrelation\Resolvers\GenericCorrelationResolver;
use App\Services\Pipeliner\RecordCorrelation\Resolvers\OpportunityCorrelationResolver;
use App\Services\Pipeliner\Strategies\Contracts\SyncStrategy;

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