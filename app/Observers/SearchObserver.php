<?php

namespace App\Observers;

use App\Jobs\IndexModel;
use Elasticsearch\Client;
use Illuminate\Database\Eloquent\Model;

class SearchObserver
{
    private Client $elasticsearch;

    public function __construct(Client $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    public function saved(Model $model)
    {
        if ($model->reindexDisabled()) {
            return;
        }

        dispatch(new IndexModel($model));
    }

    public function deleted(Model $model)
    {
        rescue(function () use ($model) {
            $this->elasticsearch->delete([
                'index' => $model->getSearchIndex(),
                'id'    => $model->getKey(),
                'type'  => $model->getSearchType(),
            ]);
        });
    }
}
