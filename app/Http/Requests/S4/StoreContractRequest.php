<?php

namespace App\Http\Requests\S4;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

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
            'rfq_number' => [
                'required',
                'string',
                'min:2',
                'regex:/^[[:alnum:]]+$/i',
                'max:20',
                Rule::unique('customers', 'rfq')->whereNull('deleted_at')
            ],
            'service_levels' => 'nullable|array',
            'service_levels.*.service_level' => 'required|string|min:2',
            'quotation_valid_until' => 'required|string|date_format:m/d/Y',
            'support_start_date' => 'required|string|date_format:Y-m-d',
            'support_end_date' => 'required|string|date_format:Y-m-d',
            'invoicing_terms' => 'required|string|min:2|max:2500',
            'country' => 'required|string|size:2|exists:countries,iso_3166_2',
            'addresses' => 'array',
            'addresses.*' => 'required|array',
            'addresses.*.address_type' => 'required|string|in:Equipment,Software',
            'addresses.*.address_1' => 'required|string|min:2',

            /** Non-mandatory */
            'addresses.*.address_2' => 'nullable|string|min:2',
            'addresses.*.city' => 'nullable|string|min:2',
            'addresses.*.state' => 'nullable|string|min:2',
            'addresses.*.post_code' => 'nullable|string|min:4',
            'addresses.*.country_code' => 'nullable|string|size:2|exists:countries,iso_3166_2',
            'addresses.*.contact_name' => 'nullable|string|min:2',
            'addresses.*.contact_number' => 'nullable|string|min:4'
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

    protected function failedValidation(Validator $validator)
    {
        slack()
            ->title('Receiving RFQ / Data from S4')
            ->status([S4_CSF_01, 'Proposed RFQ' => $this->rfq_number, 'Reason' => optional($validator->errors())->first()])
            ->image(assetExternal(SN_IMG_S4RDF))
            ->queue();

        parent::{__FUNCTION__}($validator);
    }

    public function validated()
    {
        $validated = collect(parent::validated());

        $country_id = app('country.repository')->findIdByCode($this->country);

        $addresses = $this->formatAddresses();

        $dates = $this->formatDates();

        $validated = $validated->merge(compact('country_id', 'addresses'))->merge($dates);

        return $validated->toArray();
    }

    protected function formatAddresses(): array
    {
        $addresses = $this->addresses;
        $codes = data_get($addresses, '*.country_code');

        if (blank($codes)) {
            return $addresses ?? [];
        }

        $countryIds = app('country.repository')
            ->findIdByCode($codes);

        return collect($addresses)
            ->transform(function ($address) use ($countryIds) {
                $code = optional($address)['country_code'];

                if (blank($code)) {
                    return $address;
                }

                $address['country_id'] = data_get($countryIds, $code);
                return $address;
            })
            ->toArray();
    }

    protected function formatDates(): array
    {
        return collect($this->only(['support_start_date', 'support_end_date', 'quotation_valid_until']))
            ->transform(function ($date, $key) {
                $format = $key === 'quotation_valid_until' ? 'm/d/Y' : 'Y-m-d';

                return Carbon::createFromFormat($format, $date);
            })
            ->toArray();
    }
}
