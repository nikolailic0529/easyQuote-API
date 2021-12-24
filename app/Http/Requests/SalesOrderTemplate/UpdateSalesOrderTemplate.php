<?php

namespace App\Http\Requests\SalesOrderTemplate;

use App\DTO\SalesOrderTemplate\UpdateSalesOrderTemplateData;
use App\Models\BusinessDivision;
use App\Models\Company;
use App\Models\ContractType;
use App\Models\Data\Country;
use App\Models\Data\Currency;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalesOrderTemplate extends FormRequest
{
    protected ?UpdateSalesOrderTemplateData $updateSalesOrderTemplateData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'bail', 'required', 'string', 'max:191'
            ],

            'business_division_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(BusinessDivision::class, 'id'),
            ],

            'contract_type_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(ContractType::class, 'id')
            ],

            'company_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Company::class, 'id')->whereNull('deleted_at')
            ],

            'vendor_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Vendor::class, 'id')->whereNull('deleted_at')
            ],

            'countries' => [
                'bail', 'required', 'array'
            ],

            'countries.*' => [
                'bail', 'required', 'uuid', 'distinct',
                Rule::exists(Country::class, 'id')->whereNull('deleted_at')
            ],

            'currency_id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(Currency::class, 'id')
            ],
        ];
    }

    /**
     * @return \App\DTO\SalesOrderTemplate\UpdateSalesOrderTemplateData
     */
    public function getUpdateSalesOrderTemplateData(): UpdateSalesOrderTemplateData
    {
        return $this->updateSalesOrderTemplateData ??= new UpdateSalesOrderTemplateData([
            'name' => $this->input('name'),
            'business_division_id' => $this->input('business_division_id'),
            'contract_type_id' => $this->input('contract_type_id'),
            'company_id' => $this->input('company_id'),
            'vendor_id' => $this->input('vendor_id'),
            'country_ids' => $this->input('countries'),
            'currency_id' => $this->input('currency_id')
        ]);
    }
}
