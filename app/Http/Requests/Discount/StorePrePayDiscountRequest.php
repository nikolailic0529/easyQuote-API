<?php

namespace App\Http\Requests\Discount;

use App\Rules\UniqueDuration;

class StorePrePayDiscountRequest extends StoreDiscountRequest
{
    public function additionalRules()
    {
        return [
            'durations' => [
                'required',
                'array'
            ],
            'durations.*.duration' => [
                'required',
                'integer',
                'between:1,3',
                new UniqueDuration('pre_pay_discounts')
            ],
            'durations.*.value' => [
                'required',
                'numeric',
                'min:0'
            ]
        ];
    }

    public function messages()
    {
        return [
            'durations.*.duration.between' => 'The duration must be between :min and :max years.'
        ];
    }
}
