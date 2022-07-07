<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerSalesUnitIntegration;
use App\Integrations\Pipeliner\Models\SalesUnitEntity;

class RuntimeCachedSalesUnitResolver
{
    /** @var SalesUnitEntity[] */
    private array $cache = [];
    private bool $cacheLoaded = false;

    public function __construct(protected PipelinerSalesUnitIntegration $integration)
    {
    }

    public function __invoke(string $name): ?SalesUnitEntity
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