<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerSalesUnitIntegration;
use App\Integrations\Pipeliner\Models\SalesUnitEntity;
use Carbon\CarbonInterval;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as Cache;

class CachedSalesUnitResolver
{
    public function __construct(
        protected readonly PipelinerSalesUnitIntegration $integration,
        protected readonly Cache $cache = new Repository(new NullStore()),
        \DateInterval $ttl = null,
    ) {
        $this->ttl = $ttl ?? CarbonInterval::hour();
    }

    public function __invoke(string $name): ?SalesUnitEntity
    {
        $cache = $this->cache->remember(static::class, $this->ttl, function (): array {
            return collect($this->integration->getAll())->keyBy('name')->all();
        });

        return $cache[$name] ?? null;
    }
}