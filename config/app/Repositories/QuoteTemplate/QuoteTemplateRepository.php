<?php namespace App\Repositories\QuoteTemplate;

use App\Contracts\Repositories\QuoteTemplate\QuoteTemplateRepositoryInterface;
use App\Models\QuoteTemplate\QuoteTemplate;

class QuoteTemplateRepository implements QuoteTemplateRepositoryInterface
{
    protected $quoteTemplate;

    public function __construct(QuoteTemplate $quoteTemplate)
    {
        $this->quoteTemplate = $quoteTemplate;
    }

    public function find(string $id)
    {
        $quoteTemplate = $this->quoteTemplate->whereId($id)->with('templateFields.templateFieldType')->first();

        $quoteTemplate->templateFields->each(function ($field) {
            $field->type = $field->templateFieldType->name;
            $field->makeHidden('templateFieldType');
        });

        return $quoteTemplate;
    }

    public function findByCompanyVendorCountry(string $companyId, string $vendorId, string $countryId)
    {
        return $this->quoteTemplate
            ->with('templateFields')
            ->join('company_quote_template', function ($join) use ($companyId) {
                return $join->on('company_quote_template.quote_template_id', '=', 'quote_templates.id')
                    ->where('company_id', $companyId);
            })
            ->join('vendor_quote_template', function ($join) use ($vendorId) {
                return $join->on('vendor_quote_template.quote_template_id', '=', 'quote_templates.id')
                    ->where('vendor_id', $vendorId);
            })
            ->join('country_quote_template', function ($join) use ($countryId) {
                return $join->on('country_quote_template.quote_template_id', '=', 'quote_templates.id')
                    ->where('country_id', $countryId);
            })
            ->select('quote_templates.*')
            ->get();
    }
}
