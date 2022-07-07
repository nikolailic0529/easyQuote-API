<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerFieldIntegration;
use App\Integrations\Pipeliner\Models\FieldEntity;
use App\Integrations\Pipeliner\Models\FieldFilterInput;
use App\Services\Pipeliner\Exceptions\MultiplePipelinerEntitiesFoundException;

class RuntimeCachedFieldEntityResolver
{
    /** @var FieldEntity[] */
    private array $cache = [];

    public function __construct(protected readonly PipelinerFieldIntegration $integration)
    {
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws MultiplePipelinerEntitiesFoundException
     */
    public function __invoke(FieldFilterInput $filter): ?FieldEntity
    {
        $key = md5(json_encode($filter));

        if (key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $fields = $this->integration->getByCriteria($filter);

        if (count($fields) > 1) {
            throw new MultiplePipelinerEntitiesFoundException();
        }

        return $this->cache[$key] = array_shift($fields);
    }
}