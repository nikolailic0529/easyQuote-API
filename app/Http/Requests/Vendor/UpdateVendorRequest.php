<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorRequest extends FormRequest
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
                'string',
                'min:3'
            ],
            'short_code' => [
                'string',
                'min:2',
                Rule::unique('vendors')->whereNull('deleted_at')->ignore($this->vendor)
            ],
            'logo' => [
                'image',
                'max:2048'
            ],
            'countries' => [
                'array'
            ],
            'countries.*' => [
                'uuid',
                'exists:countries,id'
            ]
        ];
    }

    public function validated()
    {
        $validated = parent::validated();

        $short_code = strtoupper(data_get($validated, 'short_code'));

        return array_merge($validated, compact('short_code'));
    }
}
