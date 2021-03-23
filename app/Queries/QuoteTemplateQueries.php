<?php

namespace App\Queries;

use App\Models\Data\Country;
use App\Models\Template\QuoteTemplate;
use App\Models\Vendor;
use App\Services\ElasticsearchQuery;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Str;

class QuoteTemplateQueries
{
    protected Elasticsearch $elasticsearch;

    protected Pipeline $pipeline;

    public function __construct(Elasticsearch $elasticsearch, Pipeline $pipeline)
    {
        $this->elasticsearch = $elasticsearch;
        $this->pipeline = $pipeline;
    }

    public function paginateQuoteTemplatesQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $query = QuoteTemplate::query()
            ->select([
                'quote_templates.id',
                'quote_templates.name',
                'quote_templates.company_id',
//                'quote_templates.vendor_id',
                'quote_templates.is_system',
                'quote_templates.activated_at',
                'companies.name as company_name',
//                'vendors.name as vendor_name',
            ])
            ->addSelect([
                'country_names' => Country::query()->selectRaw("group_concat(name separator ', ')")
                    ->join('country_quote_template', function (JoinClause $joinClause) {
                        $joinClause->on('country_quote_template.country_id', 'countries.id');
                    })
                    ->whereColumn('country_quote_template.quote_template_id', 'quote_templates.id'),
            ])
            ->addSelect([
                'vendor_names' => Vendor::query()->selectRaw("group_concat(name separator ', ')")
                    ->join('quote_template_vendor', function (JoinClause $joinClause) {
                        $joinClause->on('quote_template_vendor.vendor_id', 'vendors.id');
                    })
                    ->whereColumn('quote_template_vendor.quote_template_id', 'quote_templates.id'),
            ])
            ->join('companies', function (JoinClause $join) {
                $join->on('companies.id', 'quote_templates.company_id');
            });

        if (filled($searchQuery = $request->query('search'))) {
            $hits = rescue(function () use ($searchQuery) {
                return $this->elasticsearch->search(
                    (new ElasticsearchQuery)
                        ->modelIndex(new QuoteTemplate)
                        ->queryString(Str::of($searchQuery)->start('*')->finish('*'))
                        ->toArray()
                );
            });

            $query->whereKey(data_get($hits, 'hits.hits.*._id') ?? []);
        }

        return $this->pipeline
            ->send($query)
            ->through([
                new \App\Http\Query\ActiveFirst('quote_templates.is_active'),
                \App\Http\Query\OrderByCreatedAt::class,
                \App\Http\Query\OrderByName::class,
                \App\Http\Query\QuoteTemplate\OrderByCompanyName::class,
                new \App\Http\Query\DefaultOrderBy('quote_templates.created_at'),
            ])
            ->thenReturn();

    }

    public function referencedQuery(string $id): Builder
    {
        return QuoteTemplate::query()
            ->whereKey($id)
            ->where(function (Builder $builder) {
                $builder->whereExists(
                    fn(BaseBuilder $builder) => $builder->from('quotes')->whereColumn('quotes.quote_template_id', 'quote_templates.id')->whereNull('deleted_at'),
                )->orWhereExists(
                    fn(BaseBuilder $builder) => $builder->from('quote_versions')->whereColumn('quote_versions.quote_template_id', 'quote_templates.id')->whereNull('deleted_at'),
                );
            });
    }

    public function filterRescueQuoteTemplatesByMultipleVendorsQuery(string $companyId, array $vendors, ?string $countryId): Builder
    {
        return $this->filterQuoteTemplatesByMultipleVendorsQuery($companyId, $vendors, $countryId)
            ->where('quote_templates.business_division_id', BD_RESCUE);
    }

    public function filterWorldwideQuoteTemplatesByMultipleVendorsQuery(string $companyId, array $vendors, ?string $countryId): Builder
    {
        return $this->filterQuoteTemplatesByMultipleVendorsQuery($companyId, $vendors, $countryId)
            ->where('quote_templates.business_division_id', BD_WORLDWIDE);
    }

    public function filterWorldwidePackQuoteTemplatesByCompanyQuery(string $companyId): Builder
    {
        return QuoteTemplate::query()
            ->where('quote_templates.company_id', $companyId)
            ->joinWhere('companies', 'companies.id', '=', $companyId)
            ->orderByRaw('FIELD(`quote_templates`.`id`, `companies`.`default_template_id`, NULL) desc')
            ->select('quote_templates.id', 'quote_templates.name')
            ->where('quote_templates.contract_type_id', CT_PACK);
    }

    public function filterWorldwideContractQuoteTemplatesByMultipleVendorsQuery(string $companyId, array $vendors, ?string $countryId): Builder
    {
        return $this->filterWorldwideQuoteTemplatesByMultipleVendorsQuery($companyId, $vendors, $countryId)
            ->where('quote_templates.contract_type_id', CT_CONTRACT);
    }

    public function filterQuoteTemplatesByMultipleVendorsQuery(string $companyId, array $vendors, ?string $countryId): Builder
    {
        return QuoteTemplate::query()
            ->where('quote_templates.company_id', $companyId)
            ->whereHas('vendors', function (Builder $relation) use ($vendors) {
                $relation->whereKey($vendors);
            })
            ->when(!is_null($countryId), function (Builder $builder) use ($countryId) {
                $builder->where('country_id', $countryId);
            })
            ->joinWhere('companies', 'companies.id', '=', $companyId)
            ->orderByRaw('FIELD(`quote_templates`.`id`, `companies`.`default_template_id`, NULL) desc')
            ->select('quote_templates.id', 'quote_templates.name');
    }

    public function filterRescueQuoteTemplatesQuery(string $companyId, ?string $vendorId = null, ?string $countryId = null): Builder
    {
        return $this->filterQuoteTemplatesQuery($companyId, $vendorId, $countryId)
            ->where('quote_templates.business_division_id', BD_RESCUE);
    }

    public function filterQuoteTemplatesQuery(string $companyId, ?string $vendorId = null, ?string $countryId = null): Builder
    {
        return QuoteTemplate::query()
            ->with('company:id,name', 'vendor:id,name', 'countries:id,name')
            ->where('quote_templates.company_id', $companyId)
            ->when(!is_null($vendorId), fn(Builder $builder) => $builder->where('quote_templates.vendor_id', $vendorId))
            ->when(!is_null($countryId), function (Builder $builder) use ($countryId) {
                $builder->join('country_quote_template', function ($join) use ($countryId) {
                    $join->on('quote_templates.id', '=', 'country_quote_template.quote_template_id')
                        ->where('country_id', $countryId);
                });
            })
            ->joinWhere('companies', 'companies.id', '=', $companyId)
            ->orderByRaw('field(`quote_templates`.`id`, `companies`.`default_template_id`, null) desc')
            ->select('quote_templates.*');
    }

    public function quoteTemplatesBelongToCountryQuery(string $countryId): Builder
    {
        return QuoteTemplate::query()
            ->whereHas('countries', fn(Relation $query) => $query->whereKey($countryId));
    }
}
