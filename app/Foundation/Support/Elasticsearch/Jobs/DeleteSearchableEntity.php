<?php

namespace App\Foundation\Support\Elasticsearch\Jobs;

use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Elasticsearch\Client as Elasticsearch;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteSearchableEntity implements ShouldQueue
{
    public function __construct(protected SearchableEntity $entity)
    {
    }

    public function handle(Elasticsearch $elasticsearch,
                           Config $config,
                           ExceptionHandler $exceptionHandler)
    {
        if (false === $config->get('services.elasticsearch.enabled') ?? false) {
            return;
        }

        $params = [
            'id' => $this->entity->getKey(),
            'index' => $this->entity->getSearchIndex(),
        ];

        try {
            if ($elasticsearch->exists($params)) {
                $elasticsearch->delete($params);
            }
        } catch (NoNodesAvailableException $e) {
            $exceptionHandler->report($e);
        }
    }
}
