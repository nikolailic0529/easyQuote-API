<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerStepIntegration;
use App\Integrations\Pipeliner\Models\StepEntity;
use App\Services\Pipeliner\Exceptions\MultiplePipelinerEntitiesFoundException;

class RuntimeCachedStepResolver
{
    /** @var StepEntity[] */
    private array $cache = [];
    private bool $cacheLoaded = false;

    public function __construct(protected PipelinerStepIntegration $integration)
    {
    }

    public function __invoke(string $name, string $pipelineId): ?StepEntity
    {
        $key = "$pipelineId:$name";

        if (key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $steps = $this->integration->getByCriteria(name: $name, pipelineId: $pipelineId);

        if (count($steps) > 1) {
            throw new MultiplePipelinerEntitiesFoundException();
        }

        return $this->cache[$key] = array_shift($steps);
    }
}