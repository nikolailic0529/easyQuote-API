<?php

namespace App\Queries;

use App\Http\Query\ActiveFirst;
use App\Http\Query\DefaultOrderBy;
use App\Http\Query\OrderByCreatedAt;
use App\Http\Query\OrderByName;
use App\Http\Query\QuoteTemplate\OrderByCompanyName;
use App\Http\Query\QuoteTemplate\OrderByVendorName;
use App\Models\Data\Country;
use App\Models\Template\SalesOrderTemplate;
use App\Services\ElasticsearchQuery;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Str;

class SalesOrderTemplateQueries
{
    protected Pipeline $pipeline;

    protected Elasticsearch $elasticsearch;

    public function __construct(Pipeline $pipeline, Elasticsearch $elasticsearch)
    {
        $this->pipeline = $pipeline;
        $this->elasticsearch = $elasticsearch;
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
            });

        if (filled($searchQuery = $request->query('search'))) {
            $hits = rescue(function () use ($model, $searchQuery) {
                return $this->elasticsearch->search(
                    (new ElasticsearchQuery)
                        ->modelIndex($model)
                        ->queryString('*'.trim($searchQuery, " \t\n\r\0\x0B*").'*')
                        ->toArray()
                );
            });

            $query->whereKey(data_get($hits, 'hits.hits.*._id') ?? []);
        }

        return $this->pipeline
            ->send($query)
            ->through([
                new ActiveFirst($model->qualifyColumn('is_active')),
                OrderByCreatedAt::class,
                OrderByName::class,
                OrderByCompanyName::class,
                OrderByVendorName::class,
                new DefaultOrderBy($model->qualifyColumn('created_at')),
            ])
            ->thenReturn();
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
