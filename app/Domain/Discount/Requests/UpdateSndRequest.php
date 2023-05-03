<?php

namespace App\Domain\Discount\Requests;

use App\Domain\Discount\Validation\Rules\UniqueValue;

final class UpdateSndRequest extends UpdateDiscountRequest
{
    public function additionalRules(): array
    {
        return [
            'value' => [
                'numeric',
                'min:0',
                (new UniqueValue('sn_discounts'))->ignore($this->snd),
            ],
        ];
    }
}
