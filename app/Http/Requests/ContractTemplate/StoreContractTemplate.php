<?php

namespace App\Http\Requests\ContractTemplate;

use App\DTO\ContractTemplate\CreateContractTemplateData;
use App\Models\BusinessDivision;
use App\Models\ContractType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractTemplate extends FormRequest
{
    protected ?CreateContractTemplateData $createContractTemplateData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:80',
            'business_division_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(BusinessDivision::class, 'id'),
            ],

            'contract_type_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(ContractType::class, 'id')
            ],
            'company_id' => 'required|string|uuid|exists:companies,id',
            'vendor_id' => 'required|string|uuid|exists:vendors,id',
            'countries' => 'required|array',
            'countries.*' => 'required|string|uuid|exists:countries,id',
            'currency_id' => 'nullable|string|uuid|exists:currencies,id',
            'form_data' => 'array'
        ];
    }

    public function getCreateContractTemplateData(): CreateContractTemplateData
    {
        return $this->createContractTemplateData ??= new CreateContractTemplateData([
            'name' => $this->input('name'),
            'business_division_id' => $this->input('business_division_id'),
            'contract_type_id' => $this->input('contract_type_id'),
            'company_id' => $this->input('company_id'),
            'vendor_id' => $this->input('vendor_id'),
            'countries' => $this->input('countries'),
            'currency_id' => $this->input('currency_id'),
            'form_data' => $this->input('form_data') ?? []
        ]);
    }
}
