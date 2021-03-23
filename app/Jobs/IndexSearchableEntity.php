<?php

namespace App\Jobs;

use App\Contracts\SearchableEntity;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class IndexSearchableEntity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected SearchableEntity $entity;

    /**
     * Create a new job instance.
     *
     * @param SearchableEntity $entity
     */
    public function __construct(SearchableEntity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Execute the job.
     *
     * @param Elasticsearch $elasticsearch
     * @param Config $config
     * @return void
     */
    public function handle(Elasticsearch $elasticsearch, Config $config)
    {
        if (false === ($config->get('services.search.enabled') ?? false)) {
            return;
        }

        try {

            $elasticsearch->index([
                'id' => $this->entity->getKey(),
                'index' => $this->entity->getSearchIndex(),
                'body' => $this->entity->toSearchArray(),
            ]);

        } catch (\Throwable $e) {
            report($e);
        }
    }
}
