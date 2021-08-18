<?php

namespace App\Queries;

use App\Models\Customer\WorldwideCustomer;
use App\Queries\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class WorldwideCustomerQueries
{
    public function __construct(protected Elasticsearch $elasticsearch)
    {
    }

    public function listingQuery(Request $request = null): Builder
    {
        $request ??= new Request;

        $model = new WorldwideCustomer();

        $query = $model->newQuery()
            ->select([
                'id',
                'customer_name',
                'valid_until_date',
                'support_start_date',
                'support_end_date',
                'created_at',
            ]);

        $query->doesntHave('worldwideQuotes');

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->allowOrderFields(...[
                'customer_name',
                'rfq_number',
                'valid_until_date',
                'support_start_date',
                'support_end_date',
                'created_at',
            ])
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }
}
