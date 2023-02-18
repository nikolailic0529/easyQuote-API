<?php

namespace App\Domain\Template\Requests\QuoteTemplate;

use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\Template\Queries\ContractTemplateQueries;
use App\Domain\Template\Queries\QuoteTemplateQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetQuoteTemplatesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'company_id' => ['required', 'string', 'uuid', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'vendor_id' => ['nullable', 'string', 'uuid', Rule::exists('vendors', 'id')->whereNull('deleted_at')],
            'country_id' => ['nullable', 'string', 'uuid', Rule::exists('countries', 'id')->whereNull('deleted_at')],
            'quote_template_id' => ['nullable', 'string', 'uuid', Rule::exists('quote_templates', 'id')->whereNull('deleted_at')],
            'type' => ['nullable', 'string', Rule::in(['quote', 'contract'])],
        ];
    }

    public function getTemplatesQuery(): Builder
    {
        if ($this->input('type') === 'contract') {
            /** @var \App\Domain\Template\Queries\ContractTemplateQueries $queries */
            $queries = $this->container[ContractTemplateQueries::class];

            $query = $queries->filterRescueContractServiceContractTemplates(
                companyId: $this->input('company_id'),
                vendorId: $this->input('vendor_id'),
                countryId: $this->input('country_id'),
                quoteTemplateName: QuoteTemplate::query()->whereKey($this->input('quote_template_id'))->value('name')
            );

            return $query->select($query->qualifyColumns(['id', 'name']));
        }

        /** @var \App\Domain\Template\Queries\QuoteTemplateQueries $queries */
        $queries = $this->container[QuoteTemplateQueries::class];

        $query = $queries->filterRescueQuoteTemplatesQuery(
            companyId: $this->input('company_id'),
            vendorId: $this->input('vendor_id'),
            countryId: $this->input('country_id'),
        );

        return $query->select($query->qualifyColumns(['id', 'name']));
    }
}
