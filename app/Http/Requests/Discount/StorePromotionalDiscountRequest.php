<?php namespace App\Http\Requests\Discount;

class StorePromotionalDiscountRequest extends StoreDiscountRequest
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
                'required',
                'numeric',
                'min:0'
            ]
        ];
    }
}
