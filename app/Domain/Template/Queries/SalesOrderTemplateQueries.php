<?php

namespace App\Domain\Template\Queries;

use App\Domain\Country\Models\Country;
use App\Domain\Worldwide\Models\SalesOrderTemplate;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;

class SalesOrderTemplateQueries
{
    public function __construct(protected Elasticsearch $elasticsearch)
    {
    }

    public function paginateSalesOrderTemplatesQuery(Request $request = null): Builder
    {
        $request ??= new Request();

        $model = new SalesOrderTemplate();

        $query = SalesOrderTemplate::query()
            ->select([
                $model->qualifyColumn('id'),
                $model->qualifyColumn('user_id'),
                $model->qualifyColumn('name'),
                $model->qualifyColumn('is_system'),
                'companies.name as company_name',
                'vendors.name as vendor_name',
                'country_names' => Country::query()->selectRaw("group_concat(name separator ', ')")
                    ->join($model->countries()->getTable(), function (JoinClause $joinClause) use ($model) {
                        $joinClause->on($model->countries()->getQualifiedRelatedPivotKeyName(), 'countries.id');
                    })
                    ->whereColumn($model->countries()->getQualifiedForeignPivotKeyName(), $model->getQualifiedKeyName()),
                $model->qualifyColumn('created_at'),
                $model->qualifyColumn('activated_at'),
            ])
            ->join('companies', function (JoinClause $join) use ($model) {
                $join->on('companies.id', $model->qualifyColumn('company_id'));
            })
            ->join('vendors', function (JoinClause $join) use ($model) {
                $join->on('vendors.id', $model->qualifyColumn('vendor_id'));
            })
            ->orderByDesc($model->qualifyColumn('is_active'));

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
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

    public function filterWorldwidePackSalesOrderTemplatesQuery(string $companyId, string $vendorId, string $countryId): Builder
    {
        return SalesOrderTemplate::query()
            ->select(['sales_order_templates.id', 'sales_order_templates.name'])
            ->where('business_division_id', BD_WORLDWIDE)
            ->where('contract_type_id', CT_PACK)
            ->where('company_id', $companyId)
            ->where('vendor_id', $vendorId)
            ->where('country_id', $countryId)
            ->orderBy('sales_order_templates.name');
    }

    public function filterWorldwideContractSalesOrderTemplatesQuery(string $companyId, string $vendorId, string $countryId): Builder
    {
        return SalesOrderTemplate::query()
            ->select(['sales_order_templates.id', 'sales_order_templates.name'])
            ->where('business_division_id', BD_WORLDWIDE)
            ->where('contract_type_id', CT_CONTRACT)
            ->where('company_id', $companyId)
            ->where('vendor_id', $vendorId)
            ->where('country_id', $countryId)
            ->orderBy('sales_order_templates.name');
    }
}
