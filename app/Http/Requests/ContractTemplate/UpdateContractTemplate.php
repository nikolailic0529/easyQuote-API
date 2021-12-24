<?php

namespace App\Http\Requests\ContractTemplate;

use App\DTO\ContractTemplate\UpdateContractTemplateData;
use App\Models\BusinessDivision;
use App\Models\ContractType;
use App\Models\Template\ContractTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractTemplate extends FormRequest
{
    protected ?UpdateContractTemplateData $updateContractTemplateData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'string|min:2|max:80',
            'business_division_id' => [
                'bail', 'uuid',
                Rule::exists(BusinessDivision::class, 'id'),
            ],

            'contract_type_id' => [
                'bail', 'uuid',
                Rule::exists(ContractType::class, 'id')
            ],
            'company_id' => 'string|uuid|exists:companies,id',
            'vendor_id' => 'string|uuid|exists:vendors,id',
            'currency_id' => 'nullable|string|uuid|exists:currencies,id',
            'countries' => 'array',
            'countries.*' => 'string|uuid|exists:countries,id',
            'form_data' => 'array',
            'form_values_data' => 'required_with:form_data|array',
            'data_headers' => 'nullable|array',
            'data_headers.*.key' => [
                'required',
                'string',
                Rule::in(ContractTemplate::dataHeaderKeys()),
            ],
            'data_headers.*.value' => [
                'required',
                'string',
                'min:1',
            ],
            'complete_design' => 'boolean',
        ];
    }

    public function getUpdateContractTemplateData(): UpdateContractTemplateData
    {
        /** @var ContractTemplate $contractTemplate */
        $contractTemplate = $this->route('contract_template');

        return $this->updateContractTemplateData ??= new UpdateContractTemplateData([
            'name' => $this->input('name', $contractTemplate->name),
            'business_division_id' => $this->input('business_division_id', $contractTemplate->business_division_id),
            'contract_type_id' => $this->input('contract_type_id', $contractTemplate->contract_type_id),
            'company_id' => $this->input('company_id', $contractTemplate->company_id),
            'vendor_id' => $this->input('vendor_id', $contractTemplate->vendor_id),
            'countries' => $this->input('countries', fn() => $contractTemplate->countries->modelKeys()),
            'currency_id' => $this->input('currency_id', fn() => $contractTemplate->currency_id),
            'form_data' => $this->input('form_data', $contractTemplate->form_data),
            'data_headers' => $this->input('data_headers', $contractTemplate->data_headers->all()),
            'complete_design' => $this->boolean('complete_design')
        ]);
    }
}
