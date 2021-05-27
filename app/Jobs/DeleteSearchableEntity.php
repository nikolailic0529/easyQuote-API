<?php

namespace App\Jobs;

use App\Contracts\SearchableEntity;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteSearchableEntity implements ShouldQueue
{
    protected SearchableEntity $entity;

    public function __construct(SearchableEntity $entity)
    {
        $this->entity = $entity;
    }

    public function handle(Elasticsearch $elasticsearch, Config $config)
    {
        if (false === $config->get('services.search.enabled') ?? false) {
            return;
        }

        try {

            $elasticsearch->delete([
                'id' => $this->entity->getKey(),
                'index' => $this->entity->getSearchIndex(),
            ]);

        } catch (\Throwable $e) {

            report($e);

        }
    }
}
