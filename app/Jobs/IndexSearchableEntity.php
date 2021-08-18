<?php

namespace App\Jobs;

use App\Contracts\SearchableEntity;
use Elasticsearch\Client as Elasticsearch;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class IndexSearchableEntity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(protected SearchableEntity $entity)
    {
    }

    /**
     * Execute the job.
     *
     * @param Elasticsearch $elasticsearch
     * @param Config $config
     * @param ExceptionHandler $exceptionHandler
     * @return void
     */
    public function handle(Elasticsearch    $elasticsearch,
                           Config           $config,
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
