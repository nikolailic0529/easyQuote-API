<?php

namespace App\Domain\Discount\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class UpdateDiscountRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return array_merge($this->commonRules(), $this->additionalRules());
    }

    public function messages()
    {
        return [
            'vendor_id.exists' => 'The chosen vendor should belong to the chosen country.',
        ];
    }

    protected function commonRules()
    {
        return [
            'name' => [
                'string',
                'max:60',
            ],
            'country_id' => [
                'required',
                'string',
                'uuid',
                'exists:countries,id',
            ],
            'vendor_id' => [
                'required',
                'string',
                'uuid',
                Rule::exists('country_vendor')->where('country_id', $this->country_id),
            ],
        ];
    }

    abstract public function additionalRules();
}
