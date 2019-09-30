<?php namespace App\Http\Requests\Discount;

class UpdatePromotionalDiscountRequest extends UpdateDiscountRequest
{
    public function additionalRules()
    {
        return [
            'value' => [
                'required',
                'numeric',
                'min:0'
            ],
            'minimum_limit' => [
                'numeric',
                'min:0'
            ]
        ];
    }
}
