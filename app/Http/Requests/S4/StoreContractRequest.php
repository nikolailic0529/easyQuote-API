<?php

namespace App\Http\Requests\S4;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

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
            'quotation_valid_until' => 'required|string|date_format:m/d/Y',
            'support_start_date' => 'required|string|date_format:Y-m-d',
            'support_end_date' => 'required|string|date_format:Y-m-d',
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

    protected function failedValidation(Validator $validator)
    {
        slack_client()
            ->title('Receiving RFQ / Data from S4')
            ->status([S4_CSF_01, 'Proposed RFQ' => $this->rfq_number, 'Reason' => optional($validator->errors())->first()])
            ->image(assetExternal('img/s4rdf.gif'))
            ->send();

        parent::{__FUNCTION__}($validator);
    }

    protected function passedValidation()
    {
        slack_client()
            ->title('Receiving RFQ / Data from S4')
            ->status([S4_CSS_01, 'Proposed RFQ' => $this->rfq_number])
            ->image(assetExternal('img/s4rds.gif'))
            ->send();
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

        $support_dates = collect($this->only(['support_start_date', 'support_end_date']))
            ->transform(function ($date) {
                return now()->createFromFormat('Y-m-d', $date);
            })->toArray();

        $valid_until = now()->createFromFormat('m/d/Y', $this->quotation_valid_until);

        $dates = array_merge($support_dates, compact('valid_until'));

        $validated = $validated->merge(compact('country_id', 'addresses'))->merge($dates);

        return $validated->toArray();
    }
}
