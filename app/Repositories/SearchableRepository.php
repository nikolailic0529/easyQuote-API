<?php

namespace App\Repositories;

use App\Builder\Pagination\Paginator;
use App\Contracts\ActivatableInterface;
use Illuminate\Database\Eloquent\{
    Builder,
    Model
};
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Pipeline\Pipeline;
use Closure, Arr;

abstract class SearchableRepository
{
    public function all()
    {
        $scope = filled(func_get_args()) && func_get_arg(0) instanceof Closure ? func_get_arg(0) : null;

        $filterableQuery = $this->filterableQuery();

        if (is_array($filterableQuery)) {
            $query = $this->filterQuery(array_shift($filterableQuery));
            isset($scope) && $scope($query);

            collect($filterableQuery)->each(function ($union) use ($query, $scope) {
                $additinalQuery = $this->filterQuery($union);
                isset($scope) && $scope($additinalQuery);

                $query->union($this->filterQuery($additinalQuery));
            });
        } else {
            $query = $this->filterQuery($filterableQuery);
            isset($scope) && $scope($query);
        }

        return $query->apiPaginate();
    }

    public function search(string $query = '')
    {
        $scope = count(func_get_args()) > 1 && func_get_arg(1) instanceof Closure ? func_get_arg(1) : null;

        return $this->searchBuilder($query, $scope)->apiPaginate();
    }

    public function searchBuilder(string $query = '', ?Closure $scope = null): Builder
    {
        $model = $this->searchableModel();

        $items = $this->searchOnElasticsearch($model, $this->searchableFields(), $query);

        if ($model instanceof ActivatableInterface) {
            $activated = $this->buildQuery($model, $items, function ($query) use ($scope) {
                isset($scope) && $scope($query);
                $this->searchableScope($query)->activated();
                $this->filterQuery($query);
            });

            $deactivated = $this->buildQuery($model, $items, function ($query) use ($scope) {
                isset($scope) && $scope($query);
                $this->searchableScope($query)->deactivated();
                $this->filterQuery($query);
            });

            $builder = $activated->union($deactivated);
        } else {
            $builder = $this->buildQuery($model, $items, function ($query) use ($scope) {
                isset($scope) && $scope($query);
                $this->searchableScope($query);
                $this->filterQuery($query);
            });
        }

        return $builder;
    }

    protected function elasticsearch()
    {
        return app(Elasticsearch::class);
    }

    protected function buildQuery(Model $model, array $items, Closure $scope = null): Builder
    {
        $ids = Arr::pluck($items['hits']['hits'], '_id');

        $query = $model->query();

        if (is_callable($scope)) {
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

        $items = $this->elasticsearch()->search([
            'index' => $model->getSearchIndex(),
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
     * Searchable Scope which will be applied for Query Builder.
     *
     * @param Builder
     * @return Builder
     */
    protected function searchableScope(Builder $query)
    {
        return $query;
    }

    /**
     * Searchable Eloquent Model.
     *
     * @return Model
     */
    abstract protected function searchableModel(): Model;

    /**
     * Searchable Fields.
     *
     * @return array
     */
    abstract protected function searchableFields(): array;

    /**
     * Filtarable Eloquent Query.
     *
     * @return Builder|array
     */
    abstract protected function filterableQuery();

    /**
     * Filter Query over Classes Array.
     *
     * @return array
     */
    abstract protected function filterQueryThrough(): array;
}
