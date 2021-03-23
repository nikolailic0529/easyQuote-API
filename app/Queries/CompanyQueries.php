<?php

namespace App\Queries;

use App\Http\Query\ActiveFirst;
use App\Models\Company;
use App\Services\ElasticsearchQuery;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Str;

class CompanyQueries
{
    protected Pipeline $pipeline;

    protected Elasticsearch $elasticsearch;

    public function __construct(Pipeline $pipeline, Elasticsearch $elasticsearch)
    {
        $this->pipeline = $pipeline;
        $this->elasticsearch = $elasticsearch;
    }

    public function paginateExternalCompaniesQuery(?Request $request = null): Builder
    {
        $request ??= new Request;

        $model = new Company();

        $query = Company::query()
            ->where('type', 'External');

        if (filled($searchQuery = $request->query('search'))) {
            $hits = rescue(function () use ($model, $searchQuery) {
                return $this->elasticsearch->search(
                    (new ElasticsearchQuery)
                        ->modelIndex($model)
                        ->queryString(Str::of($searchQuery)->start('*')->finish('*'))
                        ->toArray()
                );
            });

            $query->whereKey(data_get($hits, 'hits.hits.*._id') ?? []);
        }

        return $this->pipeline
            ->send($query)
            ->through([
                \App\Http\Query\ActiveFirst::class,
                \App\Http\Query\OrderByCreatedAt::class,
                \App\Http\Query\OrderByName::class,
                \App\Http\Query\Company\OrderByVat::class,
                \App\Http\Query\Company\OrderByPhone::class,
                \App\Http\Query\Company\OrderByWebsite::class,
                \App\Http\Query\Company\OrderByEmail::class,
                \App\Http\Query\Company\OrderByCategory::class,
                \App\Http\Query\DefaultOrderBy::class,
            ])
            ->thenReturn();
    }
}
