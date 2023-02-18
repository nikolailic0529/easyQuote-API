<?php

namespace App\Domain\Discount\Requests;

use App\Domain\Discount\Validation\Rules\UniqueValue;

class UpdatePromotionalDiscountRequest extends UpdateDiscountRequest
{
    public function additionalRules()
    {
        return [
            'value' => [
                'required',
                'numeric',
                'min:0',
                (new UniqueValue('promotional_discounts'))->ignore($this->promotion),
            ],
            'minimum_limit' => [
                'numeric',
                'min:0',
            ],
        ];
    }
}
