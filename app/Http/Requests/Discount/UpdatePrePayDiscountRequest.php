<?php namespace App\Http\Requests\Discount;

class UpdatePrePayDiscountRequest extends UpdateDiscountRequest
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
