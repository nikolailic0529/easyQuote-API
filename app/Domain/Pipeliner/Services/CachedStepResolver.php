<?php

namespace App\Domain\Pipeliner\Services;

use App\Domain\Pipeliner\Integration\GraphQl\PipelinerStepIntegration;
use App\Domain\Pipeliner\Integration\Models\StepEntity;
use App\Domain\Pipeliner\Services\Exceptions\MultiplePipelinerEntitiesFoundException;
use Carbon\CarbonInterval;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as Cache;

class CachedStepResolver
{
    protected readonly \DateInterval $ttl;

    public function __construct(
        protected readonly PipelinerStepIntegration $integration,
        protected readonly Cache $cache = new Repository(new NullStore()),
        \DateInterval $ttl = null,
    ) {
        $this->ttl = $ttl ?? CarbonInterval::hour();
    }

    public function __invoke(string $name, string $pipelineId): ?StepEntity
    {
        return $this->cache->remember(static::class."$pipelineId:$name", $this->ttl,
            function () use ($pipelineId, $name): ?StepEntity {
                $steps = $this->integration->getByCriteria(name: $name, pipelineId: $pipelineId);

                if (count($steps) > 1) {
                    throw new MultiplePipelinerEntitiesFoundException();
                }

                return array_shift($steps);
            });
    }
}
