<?php

namespace App\Domain\Pipeliner\Services;

class CachedFieldApiNameResolver
{
    /** @var string[] */
    private array $cache = [];

    public function __invoke(string $entityName, string $reference): string
    {
        $key = "$entityName:$reference";

        if (key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $apiName = \App\Domain\Pipeliner\Models\PipelinerCustomField::query()
                ->where('entity_name', $entityName)
                ->where('eq_reference', $reference)
                ->value('api_name') ?? $reference;

        return $this->cache[$key] = $apiName;
    }
}
