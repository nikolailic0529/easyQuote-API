<?php namespace App\Http\Requests\Discount;

use App\Rules\UniqueValue;

class StorePromotionalDiscountRequest extends StoreDiscountRequest
{
    public function additionalRules()
    {
        return [
            'value' => [
                'required',
                'numeric',
                'min:0',
                new UniqueValue('promotional_discounts')
            ],
            'minimum_limit' => [
                'required',
                'numeric',
                'min:0'
            ]
        ];
    }
}
