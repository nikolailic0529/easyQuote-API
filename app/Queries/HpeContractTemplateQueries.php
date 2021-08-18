<?php

namespace App\Queries;

use App\Models\Template\HpeContractTemplate;
use App\Queries\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;

class HpeContractTemplateQueries
{

    public function __construct(protected Elasticsearch $elasticsearch)
    {
    }

    public function paginateHpeContractTemplatesQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $model = new HpeContractTemplate();

        $query = $model->newQuery()
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
            })
            ->orderByDesc($model->qualifyColumn('is_active'));

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->allowOrderFields(...[
                'created_at',
                'name',
                'company_name',
                'vendor_name',
            ])
            ->qualifyOrderFields(
                created_at: $model->getQualifiedCreatedAtColumn(),
                name: $model->qualifyColumn('name'),
            )
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }

    public function referencedQuery(string $id)
    {
        return HpeContractTemplate::query()
            ->whereKey($id)
            ->where(function (Builder $builder) {
                $builder->whereExists(
                    fn(BaseBuilder $builder) => $builder->from('hpe_contracts')->whereColumn('hpe_contracts.quote_template_id', 'hpe_contract_templates.id')->whereNull('deleted_at'),
                );
            });
    }
}
