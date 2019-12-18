<?php

namespace App\Http\Requests\S4;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'customer_name' => 'required|string|min:2',
            'rfq_number' => 'required|string|min:2|regex:/^[[:alnum:]]+$/i|max:20|unique:customers,rfq',
            'service_levels' => 'nullable|array',
            'service_levels.*.service_level' => 'required|string|min:2',
            'quotation_valid_until' => 'required|date',
            'support_start_date' => 'string|required',
            'support_end_date' => 'string|required',
            'payment_terms' => 'required|string|min:2|max:2500',
            'invoicing_terms' => 'required|string|min:2|max:2500',
            'country' => 'required|string|size:2|exists:countries,iso_3166_2',
            'addresses' => 'array',
            'addresses.*' => 'required|array',
            'addresses.*.address_type' => 'required|string|in:Equipment,Software',
            'addresses.*.address_1' => 'required|string|min:2',
            'addresses.*.address_2' => 'nullable|string|min:2',
            'addresses.*.city' => 'required|string|min:2',
            'addresses.*.state' => 'required|string|min:2',
            'addresses.*.post_code' => 'required|string|min:4',
            'addresses.*.country_code' => 'required|string|size:2|exists:countries,iso_3166_2',
            'addresses.*.contact_name' => 'required|string|min:2',
            'addresses.*.contact_number' => 'required|string|min:4'
        ];
    }

    public function messages()
    {
        return [
            'rfq_number.unique' => 'A Quote already exists for the provided RFQ.'
        ];
    }

    protected function prepareForValidation()
    {
        report_logger(['message' => S4_CS_02], $this->toArray());

        $rfq_number = strtoupper($this->rfq_number);

        $this->merge(compact('rfq_number'));
    }

    public function validated()
    {
        $validated = collect(parent::validated());

        $country_id = app('country.repository')->findIdByCode($this->country);

        $addresses = collect($validated->get('addresses'))->transform(function ($address) {
            $country_id = app('country.repository')->findIdByCode(data_get($address, 'country_code'));
            data_set($address, 'country_id', $country_id);
            return $address;
        });

        $validated = $validated->merge(compact('country_id', 'addresses'));

        return $validated->toArray();
    }
}
