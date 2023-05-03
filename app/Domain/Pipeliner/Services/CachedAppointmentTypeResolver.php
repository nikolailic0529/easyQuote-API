<?php

namespace App\Domain\Pipeliner\Services;

use App\Domain\Pipeliner\Integration\GraphQl\PipelinerAppointmentTypeIntegration;
use App\Domain\Pipeliner\Integration\Models\AppointmentTypeEntity;
use Carbon\CarbonInterval;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as Cache;

class CachedAppointmentTypeResolver
{
    protected readonly \DateInterval $ttl;

    public function __construct(
        protected readonly PipelinerAppointmentTypeIntegration $integration,
        protected readonly Cache $cache = new Repository(new NullStore()),
        \DateInterval $ttl = null,
    ) {
        $this->ttl = $ttl ?? CarbonInterval::hour();
    }

    public function __invoke(string $name): ?AppointmentTypeEntity
    {
        $cache = $this->cache->remember(static::class, $this->ttl, function (): array {
            return collect($this->integration->getAll())->keyBy('name')->all();
        });

        return $cache[$name] ?? null;
    }
}
