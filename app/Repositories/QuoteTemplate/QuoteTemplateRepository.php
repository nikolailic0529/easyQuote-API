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
            ->whereHas(
                'companies', function ($query) use ($companyId) {
                    return $query->whereId($companyId);
                }
            )
            ->whereHas(
                'vendors', function ($query) use ($vendorId) {
                    return $query->whereId($vendorId);
                }
            )
            ->whereHas(
                'countries', function ($query) use ($countryId) {
                    return $query->whereId($countryId);
                }
            )->get();
    }
}
