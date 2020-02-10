<?php

namespace App\Observers;

use App\Jobs\IndexModel;
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

        dispatch(new IndexModel($model));
    }

    public function deleted($model)
    {
        if (app()->runningInConsole()) {
            return;
        }

        rescue(function () use ($model) {
            $this->elasticsearch->delete([
                'index' => $model->getSearchIndex(),
                'id'    => $model->getKey(),
                'type'  => $model->getSearchType(),
            ]);
        });
    }
}
