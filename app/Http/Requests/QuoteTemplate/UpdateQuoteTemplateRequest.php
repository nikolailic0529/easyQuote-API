<?php

namespace App\Http\Requests\QuoteTemplate;

use App\DTO\QuoteTemplate\UpdateQuoteTemplateData;
use App\Models\BusinessDivision;
use App\Models\ContractType;
use App\Models\Template\QuoteTemplate;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuoteTemplateRequest extends FormRequest
{
    protected ?UpdateQuoteTemplateData $updateQuoteTemplateData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'string|max:80',
            'business_division_id' => [
                'bail', 'uuid',
                Rule::exists(BusinessDivision::class, 'id'),
            ],
            'contract_type_id' => [
                'bail', 'uuid',
                Rule::exists(ContractType::class, 'id'),
            ],

            'company_id' => 'string|uuid|exists:companies,id',

            'vendors' => [
                'bail', 'array'
            ],

            'vendors.*' => [
                'bail', 'uuid', Rule::exists(Vendor::class, 'id')->whereNull('deleted_at')
            ],

            'currency_id' => 'nullable|string|uuid|exists:currencies,id',

            'countries' => 'array',

            'countries.*' => 'string|uuid|exists:countries,id',

            'form_data' => 'array',

            'form_values_data' => 'required_with:form_data|array',

            'data_headers' => 'nullable|array',

            'data_headers.*.key' => [
                'required',
                'string',
                Rule::in(QuoteTemplate::dataHeaderKeys()),
            ],
            'data_headers.*.value' => [
                'required',
                'string',
                'min:1',
            ],
            'complete_design' => 'boolean',
        ];
    }

    public function getUpdateQuoteTemplateData(): UpdateQuoteTemplateData
    {
        /** @var QuoteTemplate $quoteTemplate */
        $quoteTemplate = $this->route('template');

        return $this->updateQuoteTemplateData ??= new UpdateQuoteTemplateData([
            'name' => $this->input('name') ?? $quoteTemplate->name,
            'business_division_id' => $this->input('business_division_id') ?? $quoteTemplate->business_division_id,
            'contract_type_id' => $this->input('contract_type_id') ?? $quoteTemplate->contract_type_id,
            'company_id' => $this->input('company_id') ?? $quoteTemplate->company_id,
            'vendors' => $this->input('vendors') ?? $quoteTemplate->vendors()->pluck('id')->all(),
            'countries' => $this->input('countries') ?? $quoteTemplate->countries()->pluck('id')->all(),
            'currency_id' => $this->input('currency_id') ?? $quoteTemplate->currency_id,
            'form_data' => $this->input('form_data') ?? $quoteTemplate->form_data,
            'data_headers' => $this->input('data_headers') ?? $quoteTemplate->data_headers->all(),
            'complete_design' => $this->boolean('complete_design')
        ]);
    }
}
