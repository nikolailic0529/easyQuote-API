<?php

namespace App\Domain\Shared\Eloquent\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class PaginationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        DatabaseBuilder::macro('apiGetCountForPagination', $this->apiGetCountForPagination());

        Builder::macro('apiPaginate', $this->apiPaginateMacro());

        DatabaseBuilder::macro('runPaginationCountQueryUsing', $this->runPaginationCountQueryUsing());

        Builder::macro('runPaginationCountQueryUsing', $this->runPaginationCountQueryUsing());

        DatabaseBuilder::macro('apiPaginate', $this->apiPaginateMacro());
    }

    protected function runPaginationCountQueryUsing(): \Closure
    {
        return function ($countQuery) {
            if ($countQuery instanceof Builder) {
                $countQuery = $countQuery->toBase();
            }

            if (!$countQuery instanceof DatabaseBuilder) {
                throw new \InvalidArgumentException(sprintf('Query must be either instance of %s or %s', Builder::class, DatabaseBuilder::class));
            }

            $query = $this;

            if ($this instanceof Builder) {
                $query = $this->getQuery();
            }

            $query->runPaginationCountQueryUsing = $countQuery;

            return $this;
        };
    }

    protected function apiPaginateMacro(): \Closure
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
            if (isset($this->runPaginationCountQueryUsing)) {
                $results = $this->runPaginationCountQueryUsing->runPaginationCountQuery($columns);
            } else {
                $results = $this->runPaginationCountQuery($columns);
            }

            if (!isset($results[0])) {
                return 0;
            } elseif (is_object($results[0])) {
                return (int) $results[0]->aggregate;
            }

            return (int) array_change_key_case((array) $results[0])['aggregate'];
        };
    }
}
