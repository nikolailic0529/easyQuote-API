<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerAppointmentTypeIntegration;
use App\Integrations\Pipeliner\Models\AppointmentTypeEntity;

class RuntimeCachedAppointmentTypeResolver
{
    /** @var AppointmentTypeEntity[] */
    private array $cache = [];
    private bool $cacheLoaded = false;

    public function __construct(protected PipelinerAppointmentTypeIntegration $integration)
    {
    }

    public function __invoke(string $name): ?AppointmentTypeEntity
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