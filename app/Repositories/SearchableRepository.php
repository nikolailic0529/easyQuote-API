<?php namespace App\Repositories;

use Illuminate\Database\Eloquent \ {
    Builder,
    Model
};
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Pipeline\Pipeline;
use Closure, Arr;

abstract class SearchableRepository
{
    protected $search;

    public function __construct()
    {
        $this->search = app()->make(Elasticsearch::class);
    }

    protected function buildQuery(Model $model, array $items, Closure $scope = null): Builder
    {
        $ids = Arr::pluck($items['hits']['hits'], '_id');

        $query = $model->query();

        if(is_callable($scope)) {
            call_user_func($scope, $query);
        }

        return $query->whereIn("{$model->getTable()}.id", $ids);
    }

    protected function searchOnElasticsearch(Model $model, array $fields = [], string $query = '')
    {
        $body = [
            'query' => [
                'multi_match' => [
                    'fields' => $fields,
                    'type' => 'phrase_prefix',
                    'query' => $query
                ]
            ]
        ];

        $items = $this->search->search([
            'index' => $model->getSearchIndex(),
            'type' => $model->getSearchType(),
            'body' => $body
        ]);

        return $items;
    }

    protected function filterQuery(Builder $query)
    {
        return app(Pipeline::class)
            ->send($query)
            ->through($this->filterQueryThrough())
            ->thenReturn();
    }

    /**
     * Filter Query over Classes Array
     *
     * @return array
     */
    abstract protected function filterQueryThrough(): array;
}
