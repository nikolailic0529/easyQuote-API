<?php

namespace App\Queries;

use App\Models\Template\ContractTemplate;
use App\Services\ElasticsearchQuery;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;

class ContractTemplateQueries
{
    protected Pipeline $pipeline;

    protected Elasticsearch $elasticsearch;

    public function __construct(Pipeline $pipeline, Elasticsearch $elasticsearch)
    {
        $this->pipeline = $pipeline;
        $this->elasticsearch = $elasticsearch;
    }

    public function paginateContractTemplatesQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $query = ContractTemplate::query()
            ->with('countries:id,name')
            ->select([
                'contract_templates.id',
                'contract_templates.name',
                'contract_templates.user_id',
                'contract_templates.company_id',
                'contract_templates.vendor_id',
                'contract_templates.is_system',
                'contract_templates.activated_at',
                'companies.name as company_name',
                'vendors.name as vendor_name',
            ])
            ->join('vendors', function (JoinClause $joinClause) {
                $joinClause->on('vendors.id', 'contract_templates.vendor_id');
            })
            ->join('companies', function (JoinClause $join) {
                $join->on('companies.id', 'contract_templates.company_id');
            });

        if (filled($searchQuery = $request->query('search'))) {
            $hits = rescue(function () use ($searchQuery) {
                return $this->elasticsearch->search(
                    ElasticsearchQuery::new()
                        ->modelIndex(new ContractTemplate())
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
                new \App\Http\Query\ActiveFirst('contract_templates.is_active'),
                \App\Http\Query\OrderByCreatedAt::class,
                \App\Http\Query\OrderByName::class,
                \App\Http\Query\QuoteTemplate\OrderByCompanyName::class,
                \App\Http\Query\QuoteTemplate\OrderByVendorName::class,
                new \App\Http\Query\DefaultOrderBy('contract_templates.created_at'),
            ])
            ->thenReturn();
    }

    public function referencedQuery(string $id)
    {
        return ContractTemplate::query()
            ->whereKey($id)
            ->where(function (Builder $builder) {
                $builder->whereExists(
                    fn(BaseBuilder $builder) => $builder->from('quotes')->whereColumn('quotes.contract_template_id', 'contract_templates.id')->whereNull('deleted_at'),
                )->orWhereExists(
                    fn(BaseBuilder $builder) => $builder->from('contracts')->whereColumn('contracts.contract_template_id', 'contract_templates.id')->whereNull('deleted_at'),
                );
            });
    }

    public function filterContractTemplatesQuery(string $companyId, ?string $vendorId = null, ?string $countryId = null, ?string $quoteTemplateName = null): Builder
    {
        return ContractTemplate::query()
            ->where('contract_templates.company_id', $companyId)
            ->when(!is_null($vendorId), fn(Builder $builder) => $builder->where('contract_templates.vendor_id', $vendorId))
            ->when(!is_null($countryId), function (Builder $builder) use ($countryId) {
                $builder->join('country_contract_template', function ($join) use ($countryId) {
                    $join->on('contract_templates.id', '=', 'country_contract_template.contract_template_id')
                        ->where('country_id', $countryId);
                });
            })
            ->joinWhere('companies', 'companies.id', '=', $companyId)
            ->when(!is_null($quoteTemplateName), function ($query) use ($quoteTemplateName) {
                $query->orderByRaw('field(`contract_templates`.`name`, ?, null) desc', [$quoteTemplateName]);
            })
            ->orderByRaw('field(`contract_templates`.`id`, `companies`.`default_template_id`, null) desc')
            ->select('contract_templates.*');
    }

    public function filterRescueContractServiceContractTemplates(string $companyId, ?string $vendorId = null, ?string $countryId = null, ?string $quoteTemplateName = null): Builder
    {
        return $this->filterContractTemplatesQuery(
            $companyId,
            $vendorId,
            $countryId,
            $quoteTemplateName
        )
            ->where('contract_templates.business_division_id', BD_RESCUE)
            ->where('contract_templates.contract_type_id', CT_CONTRACT);
    }

    public function filterWorldwideContractServiceContractTemplatesQuery(string $companyId, string $vendorId, string $countryId): Builder
    {
        return $this->filterContractTemplatesQuery(
            $companyId,
            $vendorId,
            $countryId
        )
            ->where('contract_templates.business_division_id', BD_WORLDWIDE)
            ->where('contract_templates.contract_type_id', CT_CONTRACT)
            ->select('contract_templates.id', 'contract_templates.name');
    }

    public function filterWorldwidePackContractTemplatesQuery(string $companyId, string $vendorId, string $countryId): Builder
    {
        return $this->filterContractTemplatesQuery(
            $companyId,
            $vendorId,
            $countryId
        )
            ->where('contract_templates.business_division_id', BD_WORLDWIDE)
            ->where('contract_templates.contract_type_id', CT_PACK)
            ->select('contract_templates.id', 'contract_templates.name');
    }
}
