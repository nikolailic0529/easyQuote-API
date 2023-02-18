<?php

namespace App\Domain\Rescue\Requests;

use App\Domain\Rescue\DataTransferObjects\S4Address;
use App\Domain\Rescue\DataTransferObjects\S4AddressCollection;
use App\Domain\Rescue\DataTransferObjects\S4CustomerData;
use App\Domain\Rescue\Events\Customer\RfqReceived;
use App\Domain\Rescue\Models\Customer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    protected ?S4CustomerData $s4CustomerData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'customer_name' => 'required|string|min:2',
            'rfq_number' => ['bail', 'required', 'string', 'min:2', 'regex:/^[[:alnum:]]+$/i', 'max:20', Rule::unique('customers', 'rfq')->whereNull('deleted_at')],
            'service_levels' => 'nullable|array',
            'service_levels.*.service_level' => 'bail|required|string|min:2',
            'quotation_valid_until' => 'bail|required|string|date_format:m/d/Y',
            'support_start_date' => 'bail|required|string|date_format:Y-m-d',
            'support_end_date' => 'bail|required|string|date_format:Y-m-d',
            'invoicing_terms' => 'bail|required|string|min:2|max:2500',
            'country' => ['bail', 'required', 'string', 'size:2', Rule::exists('countries', 'iso_3166_2')->whereNull('deleted_at')],
            'addresses' => 'array',
            'addresses.*' => 'bail|required|array',
            'addresses.*.address_type' => 'bail|required|string|in:Equipment,Software',
            'addresses.*.address_1' => 'bail|required|string|min:2',

            /* Non-mandatory */
            'addresses.*.address_2' => 'nullable|string|min:2',
            'addresses.*.city' => 'nullable|string|min:2',
            'addresses.*.state' => 'nullable|string|min:2',
            'addresses.*.post_code' => 'nullable|string|min:4',
            'addresses.*.country_code' => 'nullable|string|size:2|exists:countries,iso_3166_2',
            'addresses.*.contact_name' => 'nullable|string|min:2',
            'addresses.*.contact_number' => 'nullable|string|min:4',
            'addresses.*.contact_email' => 'nullable|string|email',
        ];
    }

    public function messages()
    {
        return [
            'rfq_number.unique' => 'A Quote already exists for the provided RFQ.',
        ];
    }

    public function dispatchReceivedEvent(Customer $customer): void
    {
        event(new RfqReceived($customer, $this->get('client_name', 'service')));
    }

    protected function prepareForValidation()
    {
        customlog(['message' => S4_CS_02], $this->toArray());

        $rfq_number = strtoupper($this->rfq_number);

        $this->merge(compact('rfq_number'));
    }

    protected function failedValidation(Validator $validator)
    {
        slack()
            ->title('Receiving RFQ / Data from S4')
            ->status([S4_CSF_01, 'Proposed RFQ' => $this->rfq_number, 'Reason' => optional($validator->errors())->first()])
            ->image(assetExternal(SN_IMG_S4RDF))
            ->queue();

        parent::{__FUNCTION__}($validator);
    }

    public function getS4CustomerData(): S4CustomerData
    {
        return $this->s4CustomerData ??= transform(true, function () {
            $addresses = new S4AddressCollection(S4Address::arrayOf($this->input('addresses') ?? []));

            return new S4CustomerData([
                'customer_name' => $this->input('customer_name'),
                'rfq_number' => $this->input('rfq_number'),
                'service_levels' => $this->input('service_levels'),
                'quotation_valid_until' => transform($this->input('quotation_valid_until'), function ($date) {
                    return Carbon::createFromFormat('m/d/Y', $date);
                }),
                'support_start_date' => transform($this->input('support_start_date'), function ($date) {
                    return Carbon::createFromFormat('Y-m-d', $date);
                }),
                'support_end_date' => transform($this->input('support_end_date'), function ($date) {
                    return Carbon::createFromFormat('Y-m-d', $date);
                }),
                'invoicing_terms' => $this->input('invoicing_terms'),
                'country_code' => $this->input('country'),
                'addresses' => $addresses,
            ]);
        });
    }
}
