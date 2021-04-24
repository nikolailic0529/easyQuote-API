<?php

namespace App\Http\Requests\Discount;

use App\Rules\UniqueValue;

class UpdatePromotionalDiscountRequest extends UpdateDiscountRequest
{
    public function additionalRules()
    {
        return [
            'value' => [
                'required',
                'numeric',
                'min:0',
                (new UniqueValue('promotional_discounts'))->ignore($this->promotion)
            ],
            'minimum_limit' => [
                'numeric',
                'min:0'
            ]
        ];
    }
}
