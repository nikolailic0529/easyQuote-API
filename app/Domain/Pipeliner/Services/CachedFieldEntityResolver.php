<?php

namespace App\Domain\Pipeliner\Services;

use App\Domain\Pipeliner\Integration\GraphQl\PipelinerFieldIntegration;
use App\Domain\Pipeliner\Integration\Models\FieldEntity;
use App\Domain\Pipeliner\Integration\Models\FieldFilterInput;
use App\Domain\Pipeliner\Services\Exceptions\MultiplePipelinerEntitiesFoundException;
use Carbon\CarbonInterval;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as Cache;

class CachedFieldEntityResolver
{
    protected readonly \DateInterval $ttl;

    public function __construct(
        protected readonly PipelinerFieldIntegration $integration,
        protected readonly Cache $cache = new Repository(new NullStore()),
        \DateInterval $ttl = null,
    ) {
        $this->ttl = $ttl ?? CarbonInterval::hour();
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws MultiplePipelinerEntitiesFoundException
     */
    public function __invoke(FieldFilterInput $filter): ?FieldEntity
    {
        return $this->cache->remember(static::class.md5(json_encode($filter)), $this->ttl,
            function () use ($filter): ?FieldEntity {
                $fields = $this->integration->getByCriteria($filter);

                if (count($fields) > 1) {
                    throw new MultiplePipelinerEntitiesFoundException();
                }

                return array_shift($fields);
            });
    }
}
