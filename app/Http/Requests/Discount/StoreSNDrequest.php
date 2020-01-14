<?php namespace App\Http\Requests\Discount;

use App\Rules\UniqueValue;

class StoreSNDrequest extends StoreDiscountRequest
{
    public function additionalRules()
    {
        return [
            'value' => [
                'required',
                'numeric',
                'min:0',
                new UniqueValue('sn_discounts')
            ]
        ];
    }
}
