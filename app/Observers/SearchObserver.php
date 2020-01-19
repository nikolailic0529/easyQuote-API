<?php

namespace App\Observers;

use Elasticsearch\Client;
use Illuminate\Database\Eloquent\Model;

class SearchObserver
{
    /** @var \Elasticsearch\Client */
    private $elasticsearch;

    public function __construct(Client $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    public function saved(Model $model)
    {
        if (app()->runningInConsole()) {
            return;
        }

        if ($model->reindexDisabled()) {
            return;
        }

        rescue(function () use ($model) {
            $this->elasticsearch->index([
                'index' => $model->getSearchIndex(),
                'id' => $model->getKey(),
                'body' => $model->toSearchArray(),
            ]);
        });
    }

    public function deleted($model)
    {
        if (app()->runningInConsole()) {
            return;
        }

        rescue(function () use ($model) {
            $this->elasticsearch->delete([
                'index' => $model->getSearchIndex(),
                'id' => $model->getKey(),
                'type' => $model->getSearchType(),
            ]);
        });
    }
}
