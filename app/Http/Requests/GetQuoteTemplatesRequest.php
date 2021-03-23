<?php

namespace App\Http\Requests;

use App\Models\Template\QuoteTemplate;
use App\Queries\ContractTemplateQueries;
use App\Queries\QuoteTemplateQueries;
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
            return $this->container[ContractTemplateQueries::class]->filterRescueContractServiceContractTemplates(
                $this->input('company_id'),
                $this->input('vendor_id'),
                $this->input('country_id'),
                QuoteTemplate::query()->whereKey($this->input('quote_template_id'))->value('name')
            );
        }

        return $this->container[QuoteTemplateQueries::class]->filterRescueQuoteTemplatesQuery(
            $this->input('company_id'),
            $this->input('vendor_id'),
            $this->input('country_id'),
        );
    }
}
