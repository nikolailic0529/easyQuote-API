<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerPipelineIntegration;
use App\Integrations\Pipeliner\Models\PipelineEntity;

class RuntimeCachedPipelineResolver
{
    /** @var PipelineEntity[] */
    private array $cache = [];
    private bool $cacheLoaded = false;

    public function __construct(protected PipelinerPipelineIntegration $integration)
    {
    }

    public function __invoke(string $name): ?PipelineEntity
    {
        $this->ensureCacheLoaded();

        $key = static::makeHash($name);

        return $this->cache[$key] ?? null;
    }

    private function ensureCacheLoaded(): void
    {
        if ($this->cacheLoaded) {
            return;
        }

        $this->cache = collect($this->integration->getAll())
            ->keyBy(static fn(PipelineEntity $entity): string => static::makeHash($entity->name))
            ->all();

        $this->cacheLoaded = true;
    }

    private static function makeHash(string $name): string
    {
        return md5(mb_strtolower($name));
    }
}