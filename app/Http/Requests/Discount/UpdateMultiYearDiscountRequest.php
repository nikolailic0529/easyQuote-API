<?php

namespace App\Http\Requests\Discount;

class UpdateMultiYearDiscountRequest extends UpdateDiscountRequest
{
    public function additionalRules()
    {
        return [
            'durations' => [
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
