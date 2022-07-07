<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerTaskTypeIntegration;
use App\Integrations\Pipeliner\Models\TaskTypeEntity;

class RuntimeCachedTaskTypeResolver
{
    /** @var TaskTypeEntity[] */
    private array $cache = [];
    private bool $cacheLoaded = false;

    public function __construct(protected PipelinerTaskTypeIntegration $integration)
    {
    }

    public function __invoke(string $name): ?TaskTypeEntity
    {
        $this->ensureCacheLoaded();

        return $this->cache[$name] ?? null;
    }

    private function ensureCacheLoaded(): void
    {
        if ($this->cacheLoaded) {
            return;
        }

        $this->cache = collect($this->integration->getAll())->keyBy('name')->all();

        $this->cacheLoaded = true;
    }
}