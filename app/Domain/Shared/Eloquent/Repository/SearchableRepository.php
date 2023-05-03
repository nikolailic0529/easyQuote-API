<?php

namespace App\Domain\Shared\Eloquent\Repository;

use App\Domain\Shared\Eloquent\Contracts\ActivatableInterface;
use App\Domain\Shared\Eloquent\Repository\Concerns\FiltersQuery;
use App\Foundation\Support\Elasticsearch\ElasticsearchQuery;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class SearchableRepository
{
    use FiltersQuery;

    public function all()
    {
        $scope = head(func_get_args()) instanceof \Closure ? head(func_get_args()) : null;

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
        $scope = last(func_get_args()) instanceof \Closure ? last(func_get_args()) : null;

        return $this->searchBuilder($query, $scope)->apiPaginate();
    }

    public function searchBuilder(string $search = '', ?\Closure $scope = null)
    {
        $model = $this->searchableModel();

        $items = $this->searchOnElasticsearch($model, $this->searchableFields(), $search);

        if ($model instanceof ActivatableInterface && $model instanceof Model) {
            $baseQuery = $this->buildQuery($model, $items, function ($query) use ($scope) {
                $this->searchableScope($query);
                $this->filterQuery($query, $scope);
            });

            $activated = (clone $baseQuery)->runPaginationCountQueryUsing($baseQuery)->activated();
            $deactivated = (clone $baseQuery)->deactivated();

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

    protected function buildQuery(Model $model, array $items, \Closure $scope = null)
    {
        $ids = data_get($items, 'hits.hits.*._id', []);

        $query = $this->searchableQuery();

        if (is_callable($scope)) {
            call_user_func($scope, $query);
        }

        $query->whereIn($model->getQualifiedKeyName(), $ids);

        if (is_array($unions = $query->getQuery()->unions)) {
            foreach ($unions as $union) {
                /** @var \Illuminate\Database\Eloquent\Builder */
                $unionQuery = $union['query'];

                $unionQuery->whereIn($unionQuery->getModel()->getQualifiedKeyName(), $ids);

                $query->addBinding($ids);
            }
        }

        return $query;
    }

    protected function searchOnElasticsearch(Model $model, array $fields = [], string $query = '')
    {
        $esQuery = ElasticsearchQuery::new()
            ->modelIndex($model)
            ->queryString($query)
            ->escapeQueryString();

        try {
            return $this->elasticsearch()->search($esQuery->toArray());
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * Searchable Scope which will be applied for Query Builder.
     *
     * @param mixed
     *
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
     */
    abstract protected function searchableModel(): Model;

    /**
     * Searchable Fields.
     */
    abstract protected function searchableFields(): array;

    /**
     * Filtarable Eloquent Query.
     *
     * @return Builder|array
     */
    abstract protected function filterableQuery();
}
