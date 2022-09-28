<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerDataIntegration;
use App\Integrations\Pipeliner\Models\DataEntity;
use App\Services\Pipeliner\Exceptions\MultiplePipelinerEntitiesFoundException;
use Carbon\CarbonInterval;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as Cache;

class CachedDataEntityResolver
{
    protected readonly \DateInterval $ttl;

    public function __construct(
        protected readonly PipelinerDataIntegration $integration,
        protected readonly Cache $cache = new Repository(new NullStore()),
        \DateInterval $ttl = null,
    ) {

        $this->ttl = $ttl ?? CarbonInterval::hour();
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws MultiplePipelinerEntitiesFoundException
     */
    public function __invoke(?string $id): ?DataEntity
    {
        if (blank($id)) {
            return null;
        }

        return $this->cache->remember(static::class.$id, $this->ttl, function () use ($id): ?DataEntity {
            return $this->integration->getById($id);
        });
    }
}