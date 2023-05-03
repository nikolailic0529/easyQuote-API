<?php

namespace App\Domain\Shared\Eloquent\Observers;

use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use App\Foundation\Support\Elasticsearch\Jobs\IndexSearchableEntity;
use Elasticsearch\Client;
use Illuminate\Database\Eloquent\Model;

class SearchObserver
{
    public function __construct(
        private readonly Client $elasticsearch
    ) {
    }

    public function saved(Model $model): void
    {
        if (!$model instanceof SearchableEntity) {
            return;
        }

        if (method_exists($model, 'reindexDisabled') && $model->reindexDisabled()) {
            return;
        }

        dispatch(new IndexSearchableEntity($model));
    }

    public function deleted(Model $model): void
    {
        rescue(function () use ($model) {
            $this->elasticsearch->delete([
                'index' => $model->getSearchIndex(),
                'id' => $model->getKey(),
            ]);
        });
    }
}
