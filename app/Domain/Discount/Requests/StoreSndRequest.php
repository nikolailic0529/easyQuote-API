<?php

namespace App\Domain\Discount\Requests;

use App\Domain\Discount\Validation\Rules\UniqueValue;

final class StoreSndRequest extends StoreDiscountRequest
{
    public function additionalRules(): array
    {
        return [
            'value' => [
                'required',
                'numeric',
                'min:0',
                new UniqueValue('sn_discounts'),
            ],
        ];
    }
}
