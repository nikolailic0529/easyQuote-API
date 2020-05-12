<?php

namespace App\Repositories;

use App\Contracts\ActivatableInterface;
use App\Repositories\Concerns\FiltersQuery;
use Illuminate\Database\Eloquent\{
    Builder,
    Model
};
use Elasticsearch\Client as Elasticsearch;
use Closure;
use Throwable;

abstract class SearchableRepository
{
    use FiltersQuery;

    public function all()
    {
        $scope = head(func_get_args()) instanceof Closure ? head(func_get_args()) : null;

        $filterableQuery = $this->filterableQuery();

        if (!is_array($filterableQuery)) {
            return $this->filterQuery($filterableQuery, $scope)->apiPaginate();
        }

        $query = $this->filterQuery(array_shift($filterableQuery), $scope);

        collect($filterableQuery)->each(fn ($union) => $query->unionAll($this->filterQuery($union, $scope)));

        return $query->apiPaginate();
    }

    public function search(string $query = '')
    {
        $scope = last(func_get_args()) instanceof Closure ? last(func_get_args()) : null;

        return $this->searchBuilder($query, $scope)->apiPaginate();
    }

    public function searchBuilder(string $search = '', ?Closure $scope = null)
    {
        $model = $this->searchableModel();

        $items = $this->searchOnElasticsearch($model, $this->searchableFields(), $search);

        if ($model instanceof ActivatableInterface && $model instanceof Model) {
            $activated = $this->buildQuery($model, $items, function ($query) use ($scope) {
                $this->searchableScope($query)->activated();
                $this->filterQuery($query, $scope);
            });

            $deactivated = $this->buildQuery($model, $items, function ($query) use ($scope) {
                $this->searchableScope($query)->deactivated();
                $this->filterQuery($query, $scope);
            });

            return $activated->unionAll($deactivated);
        }

        $builder = $this->buildQuery($model, $items, function ($query) use ($scope) {
            $this->searchableScope($query);
            $this->filterQuery($query, $scope);
        });

        return $builder;
    }

    protected function elasticsearch()
    {
        return app(Elasticsearch::class);
    }

    protected function buildQuery(Model $model, array $items, Closure $scope = null)
    {
        $ids = data_get($items, 'hits.hits.*._id', []);

        $query = $this->searchableQuery();
        $table = $model->getTable();

        if (is_callable($scope)) {
            call_user_func($scope, $query);
        }

        return $query->whereIn("{$table}.id", $ids);
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

        try {
            $items = $this->elasticsearch()->search([
                'index' => $model->getSearchIndex(),
                'body' => $body
            ]);
        } catch (Throwable $e) {
            $items = [];
        }

        return $items;
    }

    /**
     * Searchable Scope which will be applied for Query Builder.
     *
     * @param mixed
     * @return mixed
     */
    protected function searchableScope($query)
    {
        return $query;
    }

    /**
     * Searchable Query Builder Instance.
     *
     * @return mixed
     */
    protected function searchableQuery()
    {
        return $this->searchableModel()->query();
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
}
