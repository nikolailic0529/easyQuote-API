<?php

namespace App\Domain\Rescue\Requests;

use App\Domain\Address\Models\Address;
use App\Domain\Company\Enum\CompanyType;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\InternalCompany;
use App\Domain\Contact\Models\Contact;
use App\Domain\Rescue\DataTransferObjects\EQCustomerData;
use App\Domain\Rescue\Services\EqCustomerService;
use App\Domain\Vendor\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class CreateEqCustomerRequest extends FormRequest
{
    protected ?EQCustomerData $eqCustomerData = null;

    protected ?InternalCompany $internalCompany = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'int_company_id' => ['required', 'uuid', Rule::exists(Company::class, 'id')->where('type', CompanyType::INTERNAL)->whereNull('deleted_at')],

            'customer_name' => 'required|string|min:2',

            'service_levels' => 'nullable|array',
            'service_levels.*.service_level' => 'bail|required|string|min:2',

            'quotation_valid_until' => 'bail|required|string|date_format:Y-m-d',
            'support_start_date' => 'bail|required|string|date_format:Y-m-d',
            'support_end_date' => 'bail|required|string|date_format:Y-m-d',

            'invoicing_terms' => 'bail|required|string|min:2|max:2500',

            'addresses' => 'array',
            'addresses.*' => ['bail', 'required', 'uuid', Rule::exists(Address::class, 'id')->whereNull('deleted_at')],
            'contacts' => 'array',
            'contacts.*' => ['bail', 'required', 'uuid', Rule::exists(Contact::class, 'id')->whereNull('deleted_at')],

            'email' => 'nullable|string',
            'vat' => 'nullable|string',
            'phone' => 'nullable|string',

            'vendors' => 'array',
            'vendors.*' => ['uuid', Rule::exists(Vendor::class, 'id')->whereNull('deleted_at')],
        ];
    }

    public function getCompany(): InternalCompany
    {
        return $this->internalCompany ??= InternalCompany::findOrFail($this->int_company_id);
    }

    public function getEQCustomerData(): EQCustomerData
    {
        return $this->eqCustomerData ??= with(true, function () {
            $company = $this->getCompany();

            /** @var EqCustomerService $customerService */
            $customerService = $this->container[EqCustomerService::class];

            $rfqNumber = $customerService->giveNumber($company);
            $highestNumber = $customerService->getHighestNumber() + 1;

            return new EQCustomerData([
                'int_company_id' => $this->input('int_company_id'),
                'customer_name' => $this->input('customer_name'),

                'rfq_number' => $rfqNumber,
                'sequence_number' => $highestNumber,

                'service_levels' => $this->input('service_levels'),
                'quotation_valid_until' => transform($this->input('quotation_valid_until'), function ($date) {
                    return Carbon::createFromFormat('Y-m-d', $date);
                }),
                'support_start_date' => transform($this->input('support_start_date'), function ($date) {
                    return Carbon::createFromFormat('Y-m-d', $date);
                }),
                'support_end_date' => transform($this->input('support_end_date'), function ($date) {
                    return Carbon::createFromFormat('Y-m-d', $date);
                }),
                'invoicing_terms' => $this->input('invoicing_terms'),
                'address_keys' => $this->input('addresses'),
                'contact_keys' => $this->input('contacts'),
                'vendor_keys' => $this->input('vendors') ?? [],
                'vat' => $this->input('vat'),
                'email' => $this->input('email'),
            ]);
        });
    }
}
