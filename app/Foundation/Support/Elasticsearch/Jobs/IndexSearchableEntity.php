<?php

namespace App\Foundation\Support\Elasticsearch\Jobs;

use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Elasticsearch\Client as Elasticsearch;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class IndexSearchableEntity
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(protected SearchableEntity $entity)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Elasticsearch $elasticsearch,
                           Config $config,
                           ExceptionHandler $exceptionHandler)
    {
        if (false === ($config->get('services.elasticsearch.enabled') ?? false)) {
            return;
        }

        try {
            $elasticsearch->index([
                'id' => $this->entity->getKey(),
                'index' => $this->entity->getSearchIndex(),
                'body' => $this->entity->toSearchArray(),
            ]);
        } catch (NoNodesAvailableException $e) {
            $exceptionHandler->report($e);
        }
    }
}
