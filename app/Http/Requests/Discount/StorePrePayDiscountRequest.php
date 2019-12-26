<?php

namespace App\Http\Requests\Discount;

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
                'between:1,3'
            ],
            'durations.*.value' => [
                'required',
                'numeric',
                'min:0'
            ]
        ];
    }
}
