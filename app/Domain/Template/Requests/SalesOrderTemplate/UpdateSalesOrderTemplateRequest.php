<?php

namespace App\Domain\Template\Requests\SalesOrderTemplate;

use App\Domain\BusinessDivision\Models\BusinessDivision;
use App\Domain\Company\Models\Company;
use App\Domain\ContractType\Models\ContractType;
use App\Domain\Country\Models\Country;
use App\Domain\Currency\Models\Currency;
use App\Domain\Template\DataTransferObjects\UpdateSalesOrderTemplateData;
use App\Domain\Vendor\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalesOrderTemplateRequest extends FormRequest
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
                'bail', 'required', 'string', 'max:191',
            ],

            'business_division_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(BusinessDivision::class, 'id'),
            ],

            'contract_type_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(ContractType::class, 'id'),
            ],

            'company_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Company::class, 'id')->whereNull('deleted_at'),
            ],

            'vendor_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Vendor::class, 'id')->whereNull('deleted_at'),
            ],

            'countries' => [
                'bail', 'required', 'array',
            ],

            'countries.*' => [
                'bail', 'required', 'uuid', 'distinct',
                Rule::exists(Country::class, 'id')->whereNull('deleted_at'),
            ],

            'currency_id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(Currency::class, 'id'),
            ],
        ];
    }

    public function getUpdateSalesOrderTemplateData(): UpdateSalesOrderTemplateData
    {
        return $this->updateSalesOrderTemplateData ??= new UpdateSalesOrderTemplateData([
            'name' => $this->input('name'),
            'business_division_id' => $this->input('business_division_id'),
            'contract_type_id' => $this->input('contract_type_id'),
            'company_id' => $this->input('company_id'),
            'vendor_id' => $this->input('vendor_id'),
            'country_ids' => $this->input('countries'),
            'currency_id' => $this->input('currency_id'),
        ]);
    }
}
