<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Pagination\Paginator;
use Arr;

class QueryBuilderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        DatabaseBuilder::macro('runCachedPaginationCountQuery', $this->runCachedPaginationCountQuery());

        DatabaseBuilder::macro('apiGetCountForPagination', $this->apiGetCountForPagination());

        Builder::macro('apiPaginate', $this->apiPaginateMacro());

        DatabaseBuilder::macro('apiPaginate', $this->apiPaginateMacro());

        DatabaseBuilder::macro('activated', function () {
            return $this->whereNotNull("{$this->from}.activated_at")->limit(999999);
        });

        DatabaseBuilder::macro('deactivated', function () {
            return $this->whereNull("{$this->from}.activated_at")->limit(999999);
        });
    }

    protected function apiPaginateMacro()
    {
        return function (int $maxResults = null, int $defaultSize = null) {
            $maxResults = $maxResults ?? config('api-paginate.max_results');
            $defaultSize = $defaultSize ?? config('api-paginate.default_size');
            $numberParameter = config('api-paginate.number_parameter');
            $sizeParameter = config('api-paginate.size_parameter');
            $size = max((int) request()->input($sizeParameter, $defaultSize), 1);
            $size = $size > $maxResults ? $maxResults : $size;

            $page = Paginator::resolveCurrentPage($numberParameter);

            $total = $this instanceof DatabaseBuilder
                ? $this->apiGetCountForPagination()
                : $this->toBase()->apiGetCountForPagination();

            $results = $total ? $this->forPage($page, $size)->get(['*']) : collect();

            return $this->paginator($results, $total, $size, $page, [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $numberParameter,
            ])->appends(Arr::except(request()->input(), $numberParameter));
        };
    }

    protected function apiGetCountForPagination()
    {
        return function ($columns = ['*']) {
            $results = $this->runCachedPaginationCountQuery($columns);

            if (!isset($results[0])) {
                return 0;
            } elseif (is_object($results[0])) {
                return (int) $results[0]->aggregate;
            }

            return (int) array_change_key_case((array) $results[0])['aggregate'];
        };
    }

    protected function runCachedPaginationCountQuery()
    {
        return function ($columns = ['*']) {
            $without = $this->unions ? ['orders', 'limit', 'offset'] : ['columns', 'orders', 'limit', 'offset'];

            $query = $this->cloneWithout($without)
                ->cloneWithoutBindings($this->unions ? ['order'] : ['select', 'order'])
                ->setAggregate('count', $this->withoutSelectAliases($columns));

            return cache()->tags($query->from)->remember((clone $query)->toSql(), config('api-paginate.count_cache_ttl'), function () use ($query) {
                return $query->get()->all();
            });
        };
    }
}
