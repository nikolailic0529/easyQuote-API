<?php

namespace App\Http\Requests\Discount;

use App\Rules\UniqueValue;

class UpdateSNDrequest extends UpdateDiscountRequest
{
    public function additionalRules()
    {
        return [
            'value' => [
                'numeric',
                'min:0',
                (new UniqueValue('sn_discounts'))->ignore($this->snd)
            ]
        ];
    }
}
