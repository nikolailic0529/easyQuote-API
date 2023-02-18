<?php

namespace App\Domain\Pipeliner\Services;

use App\Domain\Pipeliner\Integration\GraphQl\PipelinerCurrencyIntegration;
use App\Domain\Pipeliner\Integration\Models\CurrencyEntity;
use Carbon\CarbonInterval;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as Cache;

class CachedCurrencyEntityResolver
{
    protected readonly \DateInterval $ttl;

    public function __construct(
        protected readonly PipelinerCurrencyIntegration $integration,
        protected readonly Cache $cache = new Repository(new NullStore()),
        \DateInterval $ttl = null,
    ) {
        $this->ttl = $ttl ?? CarbonInterval::hour();
    }

    public function __invoke(string $code): ?CurrencyEntity
    {
        $cache = $this->cache->remember(static::class, $this->ttl, function (): array {
            return collect($this->integration->getAll())->keyBy('code')->all();
        });

        return $cache[$code] ?? null;
    }
}
