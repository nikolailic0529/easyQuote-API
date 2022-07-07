<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerCurrencyIntegration;
use App\Integrations\Pipeliner\Models\CurrencyEntity;

class RuntimeCachedCurrencyEntityResolver
{
    /** @var CurrencyEntity[] */
    private array $cache = [];
    private bool $cacheLoaded = false;

    public function __construct(protected PipelinerCurrencyIntegration $integration)
    {
    }

    public function __invoke(string $code): ?CurrencyEntity
    {
        $this->ensureCacheLoaded();

        return $this->cache[$code] ?? null;
    }

    private function ensureCacheLoaded(): void
    {
        if ($this->cacheLoaded) {
            return;
        }

        $this->cache = collect($this->integration->getAll())->keyBy('code')->all();

        $this->cacheLoaded = true;
    }
}