<?php

namespace App\Http\Requests\QuoteTemplate;

use App\DTO\QuoteTemplate\CreateQuoteTemplateData;
use App\Models\BusinessDivision;
use App\Models\ContractType;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuoteTemplateRequest extends FormRequest
{
    protected ?CreateQuoteTemplateData $createQuoteTemplateData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:100',

            'business_division_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(BusinessDivision::class, 'id'),
            ],

            'contract_type_id' => [
              'bail', 'required', 'uuid',
              Rule::exists(ContractType::class, 'id')
            ],

            'company_id' => 'required|string|uuid|exists:companies,id',

            'vendors' => [
                'bail', 'required', 'array',
            ],

            'vendors.*' => [
                'bail', 'required', 'uuid',
                Rule::exists(Vendor::class, 'id')->whereNull('deleted_at')
            ],

            'countries' => 'required|array',

            'countries.*' => 'required|string|uuid|exists:countries,id',

            'currency_id' => 'nullable|string|uuid|exists:currencies,id',

            'form_data' => 'array',
        ];
    }

    public function getCreateQuoteTemplateData(): CreateQuoteTemplateData
    {
        return $this->createQuoteTemplateData ??= new CreateQuoteTemplateData([
            'name' => $this->input('name'),
            'business_division_id' => $this->input('business_division_id'),
            'contract_type_id' => $this->input('contract_type_id'),
            'company_id' => $this->input('company_id'),
            'vendors' => $this->input('vendors'),
            'countries' => $this->input('countries'),
            'currency_id' => $this->input('currency_id'),
            'form_data' => $this->input('form_data') ?? []
        ]);
    }
}
