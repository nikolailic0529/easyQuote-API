<?php

namespace App\Services\Pipeliner;

use App\Models\Pipeliner\PipelinerCustomField;

class RuntimeCachedFieldApiNameResolver
{
    /** @var string[] */
    private array $cache = [];

    public function __invoke(string $entityName, string $reference): string
    {
        $key = "$entityName:$reference";

        if (key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $apiName = PipelinerCustomField::query()
                ->where('entity_name', $entityName)
                ->where('eq_reference', $reference)
                ->value('api_name') ?? $reference;

        return $this->cache[$key] = $apiName;
    }
}