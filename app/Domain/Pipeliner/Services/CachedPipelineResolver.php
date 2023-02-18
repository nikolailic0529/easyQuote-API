<?php

namespace App\Domain\Pipeliner\Services;

use App\Domain\Pipeliner\Integration\GraphQl\PipelinerPipelineIntegration;
use App\Domain\Pipeliner\Integration\Models\PipelineEntity;
use Carbon\CarbonInterval;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as Cache;

class CachedPipelineResolver
{
    protected readonly \DateInterval $ttl;

    public function __construct(
        protected readonly PipelinerPipelineIntegration $integration,
        protected readonly Cache $cache = new Repository(new NullStore()),
        \DateInterval $ttl = null,
    ) {
        $this->ttl = $ttl ?? CarbonInterval::hour();
    }

    public function __invoke(string $name): ?PipelineEntity
    {
        $cache = $this->cache->remember(static::class, $this->ttl, function (): array {
            return collect($this->integration->getAll())
                ->keyBy(static fn (PipelineEntity $entity): string => static::makeHash($entity->name))
                ->all();
        });

        return $cache[static::makeHash($name)] ?? null;
    }

    private static function makeHash(string $name): string
    {
        return md5(mb_strtolower($name));
    }
}
