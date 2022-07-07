<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerClientIntegration;
use App\Integrations\Pipeliner\Models\ClientEntity;

class RuntimeCachedClientResolver
{
    /** @var ClientEntity[] */
    private array $cache = [];
    private bool $cacheLoaded = false;

    public function __construct(protected PipelinerClientIntegration $integration)
    {
    }

    public function __invoke(string $name): ?ClientEntity
    {
        $this->ensureCacheLoaded();

        return $this->cache[$name] ?? null;
    }

    private function ensureCacheLoaded(): void
    {
        if ($this->cacheLoaded) {
            return;
        }

        $this->cache = collect($this->integration->getAll())
            ->keyBy('formattedName')
            ->all();

        $this->cacheLoaded = true;
    }
}