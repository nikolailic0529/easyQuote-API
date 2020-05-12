<?php

namespace App\Http\Requests\Customer;

use App\Models\Address;
use App\Models\Company;
use App\Models\Customer\Customer;
use App\Models\Data\Country;
use App\Models\InternalCompany;
use App\Services\EqCustomerService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEqCustomer extends FormRequest
{
    protected EqCustomerService $eqCustomerService;

    protected ?InternalCompany $internalCompany = null;

    public function __construct(EqCustomerService $eqCustomerService)
    {
        $this->eqCustomerService = $eqCustomerService;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'int_company_id'                    => ['required', 'uuid', Rule::exists(Company::class, 'id')->where('type', Company::INT_TYPE)->whereNull('deleted_at')],

            // 'ext_company_id'                    => ['nullable', 'uuid', Rule::exists(Company::class, 'id')->where('type', Company::EXT_TYPE)->whereNull('deleted_at')],
            'customer_name'                     => 'required|string|min:2',

            'service_levels'                    => 'nullable|array',
            'service_levels.*.service_level'    => 'bail|required|string|min:2',

            'quotation_valid_until'             => 'bail|required|string|date_format:Y-m-d',
            'support_start_date'                => 'bail|required|string|date_format:Y-m-d',
            'support_end_date'                  => 'bail|required|string|date_format:Y-m-d',

            'invoicing_terms'                   => 'bail|required|string|min:2|max:2500',

            'country_id'                        => ['required', 'uuid', Rule::exists(Country::class, 'id')->whereNull('deleted_at')],
            'addresses'                         => 'array',
            'addresses.*'                       => ['bail', 'required', 'uuid', Rule::exists(Address::class, 'id')->whereNull('deleted_at')],
        ];
    }

    public function getCompany(): InternalCompany
    {
        if (isset($this->internalCompany)) {
            return $this->internalCompany;
        }

        return $this->internalCompany = InternalCompany::findOrFail($this->int_company_id);
    }
    
    public function validated()
    {
        $company = $this->getCompany();

        $rfqNumber = $this->eqCustomerService->giveNumber($company);
        $highestNumber = $this->eqCustomerService->getHighestNumber();

        $attributes = [
            'rfq_number' => $rfqNumber,
            'source' => Customer::EQ_SOURCE,
            'sequence_number' => ++$highestNumber
        ];

        return $attributes + parent::validated();
    }
}
