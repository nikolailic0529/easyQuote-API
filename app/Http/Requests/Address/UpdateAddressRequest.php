<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'address_type' => [
                'required',
                'string',
                Rule::in(__('address.types'))
            ],
            'address_1' => 'required|string|min:2',
            'address_2' => 'nullable|string|min:2',
            'city' => 'string|min:2',
            'state' => 'string|min:2',
            'post_code' => 'string|min:4',
            'country_id' => 'required_without:country_code|string|uuid|exists:countries,id'
        ];
    }
}
