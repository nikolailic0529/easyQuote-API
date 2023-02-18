<?php

namespace App\Domain\Discount\Requests;

use App\Domain\Discount\Validation\Rules\UniqueValue;

class StorePromotionalDiscountRequest extends StoreDiscountRequest
{
    public function additionalRules()
    {
        return [
            'value' => [
                'required',
                'numeric',
                'min:0',
                new UniqueValue('promotional_discounts'),
            ],
            'minimum_limit' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }
}
