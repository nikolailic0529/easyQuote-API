<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class StoreVendorRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'min:3'
            ],
            'short_code' => [
                'required',
                'string',
                'min:2'
            ],
            'logo' => [
                'image',
                'max:2048'
            ],
            'countries' => [
                'required',
                'array'
            ],
            'countries.*' => [
                'required',
                'uuid',
                'exists:countries,id'
            ]
        ];
    }
}
