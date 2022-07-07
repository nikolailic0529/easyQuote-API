<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerDataIntegration;
use App\Integrations\Pipeliner\Models\DataEntity;
use App\Services\Pipeliner\Exceptions\MultiplePipelinerEntitiesFoundException;

class RuntimeCachedDataEntityResolver
{
    /** @var DataEntity[] */
    private array $cache = [];

    public function __construct(protected readonly PipelinerDataIntegration $integration)
    {
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

        if (key_exists($id, $this->cache)) {
            return $this->cache[$id];
        }

        return $this->cache[$id] = $this->integration->getById($id);
    }
}