<?php

namespace App\Services;

use App\Models\Quote\Quote;
use App\Models\Quote\QuoteVersion;
use App\Models\Template\QuoteTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\JoinClause;

class QuoteTemplateQueries
{
    public function referencedQuery(string $id): Builder
    {
        return QuoteTemplate::whereKey($id)
            ->where(function (Builder $builder) {
                $builder->whereExists(
                    fn (BaseBuilder $builder) => $builder->from('quotes')->whereColumn('quotes.quote_template_id', 'quote_templates.id')->whereNull('deleted_at'),
                )->orWhereExists(
                    fn (BaseBuilder $builder) => $builder->from('quote_versions')->whereColumn('quote_versions.quote_template_id', 'quote_templates.id')->whereNull('deleted_at'),
                );
            });
    }

    public function filterQuoteTemplatesQuery(string $companyId, array $vendors, ?string $countryId): Builder
    {
        return QuoteTemplate::where('quote_templates.company_id', $companyId)
            ->whereIn('quote_templates.vendor_id', $vendors)
            ->join('country_quote_template', function (JoinClause $join) use ($countryId) {
                $join->on('quote_templates.id', '=', 'country_quote_template.quote_template_id')
                    ->where('country_id', $countryId);
            })
            ->joinWhere('companies', 'companies.id', '=', $companyId)
            ->orderByRaw('FIELD(`quote_templates`.`id`, `companies`.`default_template_id`, NULL) desc')
            ->select('quote_templates.id', 'quote_templates.name');
    }
}
