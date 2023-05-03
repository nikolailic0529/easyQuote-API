<?php

namespace App\Domain\Discount\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class UpdateDiscountRequest extends FormRequest
{
    final public function rules(): array
    {
        return array_merge($this->commonRules(), $this->additionalRules());
    }

    public function messages(): array
    {
        return [
            'vendor_id.exists' => __('discount.validation.vendor_exists'),
        ];
    }

    final protected function commonRules(): array
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
                Rule::exists('country_vendor')
                    ->where('country_id', $this->input('country_id')),
            ],
        ];
    }

    abstract public function additionalRules(): array;
}
