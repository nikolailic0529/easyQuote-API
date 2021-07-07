<?php

namespace App\Queries;

use App\Models\Template\HpeContractTemplate;
use App\Services\ElasticsearchQuery;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Str;

class HpeContractTemplateQueries
{
    protected Pipeline $pipeline;

    protected Elasticsearch $elasticsearch;

    public function __construct(Pipeline $pipeline, Elasticsearch $elasticsearch)
    {
        $this->pipeline = $pipeline;
        $this->elasticsearch = $elasticsearch;
    }

    public function paginateHpeContractTemplatesQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $query = HpeContractTemplate::query()
            ->with('countries:id,name')
            ->select([
                'hpe_contract_templates.id',
                'hpe_contract_templates.name',
                'hpe_contract_templates.user_id',
                'hpe_contract_templates.company_id',
                'hpe_contract_templates.vendor_id',
                'hpe_contract_templates.is_system',
                'hpe_contract_templates.activated_at',
                'companies.name as company_name',
                'vendors.name as vendor_name',
            ])
            ->join('vendors', function (JoinClause $joinClause) {
                $joinClause->on('vendors.id', 'hpe_contract_templates.vendor_id');
            })
            ->join('companies', function (JoinClause $join) {
                $join->on('companies.id', 'hpe_contract_templates.company_id');
            });

        if (filled($searchQuery = $request->query('search'))) {
            $hits = rescue(function () use ($searchQuery) {
                return $this->elasticsearch->search(
                    (new ElasticsearchQuery)
                        ->modelIndex(new HpeContractTemplate())
                        ->queryString(Str::of($searchQuery)->start('*')->finish('*'))
                        ->toArray()
                );
            });

            $query->whereKey(data_get($hits, 'hits.hits.*._id') ?? []);
        }

        return $this->pipeline
            ->send($query)
            ->through([
                new \App\Http\Query\ActiveFirst('hpe_contract_templates.is_active'),
                \App\Http\Query\OrderByCreatedAt::class,
                \App\Http\Query\OrderByName::class,
                \App\Http\Query\QuoteTemplate\OrderByCompanyName::class,
                \App\Http\Query\QuoteTemplate\OrderByVendorName::class,
                new \App\Http\Query\DefaultOrderBy('hpe_contract_templates.created_at'),
            ])
            ->thenReturn();
    }

    public function referencedQuery(string $id)
    {
        return HpeContractTemplate::whereKey($id)
            ->where(function (Builder $builder) {
                $builder->whereExists(
                    fn(BaseBuilder $builder) => $builder->from('hpe_contracts')->whereColumn('hpe_contracts.quote_template_id', 'hpe_contract_templates.id')->whereNull('deleted_at'),
                );
            });
    }
}
