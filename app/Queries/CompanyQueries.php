<?php

namespace App\Queries;

use App\Http\Query\ActiveFirst;
use App\Http\Query\Company\OrderByCategory;
use App\Http\Query\Company\OrderByEmail;
use App\Http\Query\Company\OrderByPhone;
use App\Http\Query\Company\OrderByVat;
use App\Http\Query\Company\OrderByWebsite;
use App\Http\Query\DefaultOrderBy;
use App\Http\Query\OrderByCreatedAt;
use App\Http\Query\OrderByName;
use App\Models\Asset;
use App\Models\Company;
use App\Services\ElasticsearchQuery;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;

class CompanyQueries
{
    protected Pipeline $pipeline;

    protected Elasticsearch $elasticsearch;

    public function __construct(Pipeline $pipeline, Elasticsearch $elasticsearch)
    {
        $this->pipeline = $pipeline;
        $this->elasticsearch = $elasticsearch;
    }

    public function listOfAssetCompaniesQuery(Asset $asset): Builder
    {
        $companyModel = $asset->companies()->getRelated();

        $query = $asset->companies()->getQuery()
            ->select([
                $companyModel->getQualifiedKeyName(),
                $companyModel->qualifyColumn('user_id'),
                $companyModel->qualifyColumn('name'),
                $companyModel->qualifyColumn('source'),
                $companyModel->qualifyColumn('type'),
                $companyModel->qualifyColumn('email'),
                $companyModel->qualifyColumn('phone'),
                $companyModel->getQualifiedCreatedAtColumn(),
            ]);

        return tap($query, function (Builder $builder) use ($companyModel) {
            $builder->orderByDesc($companyModel->getQualifiedCreatedAtColumn());
        });
    }

    public function paginateCompaniesQuery(Request $request = null): Builder
    {
        $request ??= new Request();

        $model = new Company();

        $query = $model->newQuery()
            ->with('image')
            ->select([
                'id',
                'user_id',
                'source',
                'type',
                'name',
                'email',
                'phone',
                'total_quoted_value' => function (BaseBuilder $baseBuilder) {
                    return $baseBuilder
                        ->selectRaw('SUM(total_price)')
                        ->from('quote_totals')
                        ->whereColumn('quote_totals.company_id', 'companies.id');
                },
                'created_at',
                'activated_at',
            ])
            ->withCasts([
                'total_quoted_value' => 'decimal:2',
            ]);

        if (filled($searchQuery = $request->query('search'))) {
            $hits = rescue(function () use ($model, $searchQuery) {
                return $this->elasticsearch->search(
                    ElasticsearchQuery::new()
                        ->modelIndex($model)
                        ->queryString($searchQuery)
                        ->escapeQueryString()
                        ->wrapQueryString()
                        ->toArray()
                );
            });

            $query->whereKey(data_get($hits, 'hits.hits.*._id') ?? []);
        }

        return $this->pipeline
            ->send($query)
            ->through([
                ActiveFirst::class,
                OrderByCreatedAt::class,
                OrderByName::class,
                OrderByVat::class,
                OrderByPhone::class,
                OrderByWebsite::class,
                OrderByEmail::class,
                OrderByCategory::class,
                DefaultOrderBy::class,
            ])
            ->thenReturn();
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
                    ElasticsearchQuery::new()
                        ->modelIndex($model)
                        ->queryString($searchQuery)
                        ->escapeQueryString()
                        ->wrapQueryString()
                        ->toArray()
                );
            });

            $query->whereKey(data_get($hits, 'hits.hits.*._id') ?? []);
        }

        return $this->pipeline
            ->send($query)
            ->through([
                ActiveFirst::class,
                OrderByCreatedAt::class,
                OrderByName::class,
                OrderByVat::class,
                OrderByPhone::class,
                OrderByWebsite::class,
                OrderByEmail::class,
                OrderByCategory::class,
                DefaultOrderBy::class,
            ])
            ->thenReturn();
    }
}
