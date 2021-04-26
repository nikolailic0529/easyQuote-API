<?php

namespace App\Http\Requests\SalesOrderTemplate;

use App\Models\Company;
use App\Models\Data\Country;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterSalesOrderTemplatesByCompanyVendorCountry extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'company_id' => [
                'required', 'uuid',
                Rule::exists(Company::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at')
            ],
            'vendor_id' => [
                'required', 'uuid',
                Rule::exists(Vendor::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at')
            ],
            'country_id' => [
                'required', 'uuid',
                Rule::exists(Country::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at')
            ]
        ];
    }

    public function getCompanyId(): string
    {
        return $this->input('company_id');
    }

    public function getVendorId(): string
    {
        return $this->input('vendor_id');
    }

    public function getCountryId(): string
    {
        return $this->input('country_id');
    }
}
