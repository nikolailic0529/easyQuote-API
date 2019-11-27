<?php

namespace App\Observers;

use Elasticsearch\Client;

class SearchObserver
{
    /** @var \Elasticsearch\Client */
    private $elasticsearch;

    public function __construct(Client $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    public function saved($model)
    {
        if (app()->runningInConsole()) {
            return;
        }

        try {
            $this->elasticsearch->index([
                'index' => $model->getSearchIndex(),
                'id' => $model->getKey(),
                'body' => $model->toSearchArray(),
            ]);
        } catch (\Exception $exception) {
            logger($exception->getMessage());
        }
    }

    public function deleted($model)
    {
        if (app()->runningInConsole()) {
            return;
        }
        try {
            $this->elasticsearch->delete([
                'index' => $model->getSearchIndex(),
                'type' => $model->getSearchType(),
                'id' => $model->getKey(),
            ]);
        } catch (\Exception $exception) {
            logger($exception->getMessage());
        }
    }
}
