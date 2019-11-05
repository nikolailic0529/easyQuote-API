<?php

namespace App\Http\Requests\S4;

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
            'customer_name' => 'string|min:2',
            'rfq_number' => 'required|string|min:2|regex:/^[[:alnum:]]+$/i|max:20|unique:customers,rfq',
            'service_level' => 'string|min:2',
            'quotation_valid_until' => 'date_format:m/d/Y',
            'support_start_date' => 'date_format:m/d/Y',
            'support_end_date' => 'date_format:m/d/Y',
            'payment_terms' => 'string|min:2|max:2500',
            'invoicing_terms' => 'string|min:2|max:2500',
            'addresses' => 'array',
            'addresses.*' => 'array',
            'addresses.*.address_type' => 'required|string|in:Equipment,Software',
            'addresses.*.address_1' => 'required|string|min:2',
            'addresses.*.address_2' => 'nullable|string|min:2',
            'addresses.*.city' => 'string|min:2',
            'addresses.*.state' => 'string|min:2',
            'addresses.*.post_code' => 'string|min:4',
            'addresses.*.country_code' => 'string|size:2',
            'addresses.*.contact_name' => 'string|min:2',
            'addresses.*.contact_number' => 'string|min:4'
        ];
    }
}
