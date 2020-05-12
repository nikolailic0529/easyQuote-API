<?php

namespace App\Http\Requests\Address;

use App\Models\Address;
use App\Traits\Request\PreparesNullValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAddressRequest extends FormRequest
{
    use PreparesNullValues;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'address_type' => ['required', 'string', Rule::in(Address::TYPES)],
            'address_1' => 'required|string|min:2',
            'address_2' => 'nullable|string|min:2',
            'city' => 'string|min:2',
            'state' => 'string|min:2',
            'post_code' => 'string|min:4',
            'country_id' => 'required_without:country_code|string|uuid|exists:countries,id'
        ];
    }

    protected function nullValues()
    {
        return ['address_2'];
    }
}
