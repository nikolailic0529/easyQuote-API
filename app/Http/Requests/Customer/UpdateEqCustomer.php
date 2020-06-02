<?php

namespace App\Http\Requests\Customer;

use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\InternalCompany;
use App\Models\Vendor;
use App\Models\Customer\Customer;
use App\Services\EqCustomerService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEqCustomer extends FormRequest
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

            'customer_name'                     => 'required|string|min:2',

            'service_levels'                    => 'nullable|array',
            'service_levels.*.service_level'    => 'bail|required|string|min:2',

            'quotation_valid_until'             => 'bail|string|date_format:Y-m-d',
            'support_start_date'                => 'bail|string|date_format:Y-m-d',
            'support_end_date'                  => 'bail|string|date_format:Y-m-d',

            'invoicing_terms'                   => 'bail|string|min:2|max:2500',

            'addresses'                         => 'array',
            'addresses.*'                       => ['bail', 'required', 'uuid', Rule::exists(Address::class, 'id')->whereNull('deleted_at')],
            'contacts'                          => 'array',
            'contacts.*'                        => ['bail', 'required', 'uuid', Rule::exists(Contact::class, 'id')->whereNull('deleted_at')],

            'email'                             => 'nullable|string',
            'vat'                               => 'nullable|string',
            'phone'                             => 'nullable|string',

            'vendors'                           => 'array',
            'vendors.*'                         => ['uuid', Rule::exists(Vendor::class, 'id')->whereNull('deleted_at')],
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

        $rfqNumber = $this->eqCustomerService->giveNumber($company, $this->route('eq_customer'));
        $highestNumber = $this->eqCustomerService->getHighestNumber($this->route('eq_customer'));

        $attributes = [
            'rfq_number' => $rfqNumber,
            'source' => Customer::EQ_SOURCE,
            'sequence_number' => ++$highestNumber
        ];

        return $attributes + parent::validated();
    }
}
