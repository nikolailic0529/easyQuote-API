<?php

namespace App\Http\Requests\Discount;

class StoreMultiYearDiscountRequest extends StoreDiscountRequest
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
                'between:1,5'
            ],
            'durations.*.value' => [
                'required',
                'numeric',
                'min:0'
            ]
        ];
    }
}
