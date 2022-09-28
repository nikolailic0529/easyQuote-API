<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerClientIntegration;
use App\Integrations\Pipeliner\Models\ClientEntity;
use Carbon\CarbonInterval;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as Cache;

class CachedClientResolver
{
    protected readonly \DateInterval $ttl;

    public function __construct(
        protected readonly PipelinerClientIntegration $integration,
        protected readonly Cache $cache = new Repository(new NullStore()),
        \DateInterval $ttl = null,
    ) {
        $this->ttl = $ttl ?? CarbonInterval::hour();
    }

    public function __invoke(string $name): ?ClientEntity
    {
        $cache = $this->cache->remember(static::class, $this->ttl, function (): array {
            return collect($this->integration->getAll())->keyBy('formattedName')->all();
        });

        return $cache[$name] ?? null;
    }
}