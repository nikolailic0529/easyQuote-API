<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerTaskTypeIntegration;
use App\Integrations\Pipeliner\Models\TaskTypeEntity;
use Carbon\CarbonInterval;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as Cache;

class CachedTaskTypeResolver
{
    public function __construct(
        protected readonly PipelinerTaskTypeIntegration $integration,
        protected readonly Cache $cache = new Repository(new NullStore()),
        \DateInterval $ttl = null,
    ) {
        $this->ttl = $ttl ?? CarbonInterval::hour();
    }

    public function __invoke(string $name): ?TaskTypeEntity
    {
        $cache = $this->cache->remember(static::class, $this->ttl, function (): array {
            return collect($this->integration->getAll())->keyBy('name')->all();
        });

        return $cache[$name] ?? null;
    }
}