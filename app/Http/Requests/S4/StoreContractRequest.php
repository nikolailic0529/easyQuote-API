<?php

namespace App\Http\Requests\S4;

use App\Models\Data\Country;
use Illuminate\Foundation\Http\FormRequest;
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
            'rfq_number' => 'required|string|min:2|regex:/^[[:alnum:]]+$/i|max:20|unique:customers,rfq',
            'service_level' => 'required|string|min:2',
            'quotation_valid_until' => 'required|date_format:m/d/Y',
            'support_start_date' => 'required|date_format:m/d/Y',
            'support_end_date' => 'required|date_format:m/d/Y',
            'payment_terms' => 'required|string|min:2|max:2500',
            'invoicing_terms' => 'required|string|min:2|max:2500',
            'addresses' => 'required|array',
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

    public function validated()
    {
        $validated = collect(parent::validated());

        $addresses = collect($validated->get('addresses'))->transform(function ($address) {
            $country_id = Country::code(data_get($address, 'country_code'))->firstOrFail()->id;
            data_set($address, 'country_id', $country_id);

            return $address;
        });

        $validated->put('addresses', $addresses);

        return $validated->toArray();
    }
}
