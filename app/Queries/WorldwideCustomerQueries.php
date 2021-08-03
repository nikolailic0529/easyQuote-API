<?php

namespace App\Queries;

use App\Models\Customer\WorldwideCustomer;
use App\Services\ElasticsearchQuery;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Str;

class WorldwideCustomerQueries
{
    protected Elasticsearch $elasticsearch;

    protected Pipeline $pipeline;

    public function __construct(Elasticsearch $elasticsearch, Pipeline $pipeline)
    {
        $this->elasticsearch = $elasticsearch;
        $this->pipeline = $pipeline;
    }

    public function listingQuery(Request $request = null)
    {
        $request ??= new Request;

        $query = WorldwideCustomer::select(
            'id',
            'customer_name',
            'valid_until_date',
            'support_start_date',
            'support_end_date',
            'created_at'
        );

        if (filled($searchQuery = $request->query('search'))) {
            $hits = rescue(function () use ($searchQuery) {
                return $this->elasticsearch->search(
                    ElasticsearchQuery::new()
                        ->modelIndex(new WorldwideCustomer)
                        ->queryString($searchQuery)
                        ->escapeQueryString()
                        ->wrapQueryString()
                        ->toArray()
                );
            });

            $query->whereKey(data_get($hits, 'hits.hits.*._id') ?? []);
        }

        $query->doesntHave('worldwideQuotes');

        $this->pipeline->send($query)
            ->through([
                \App\Http\Query\OrderByCustomerName::class,
                \App\Http\Query\OrderByRfqNumber::class,
                \App\Http\Query\OrderByValidUntilDate::class,
                \App\Http\Query\OrderByCustomerSupportEndDate::class,
                \App\Http\Query\OrderBySupportEndDate::class,
                \App\Http\Query\OrderByCreatedAt::class,
                \App\Http\Query\DefaultOrderBy::class,
            ])
            ->thenReturn();

        return $query;
    }
}
