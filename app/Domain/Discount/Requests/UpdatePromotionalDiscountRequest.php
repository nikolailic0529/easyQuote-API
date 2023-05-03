<?php

namespace App\Domain\Discount\Requests;

use App\Domain\Discount\Validation\Rules\UniqueValue;

final class UpdatePromotionalDiscountRequest extends UpdateDiscountRequest
{
    public function additionalRules(): array
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
