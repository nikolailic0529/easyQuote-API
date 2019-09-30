<?php namespace App\Http\Requests\Discount;

class StoreSNDrequest extends StoreDiscountRequest
{
    public function additionalRules()
    {
        return [
            'value' => [
                'required',
                'numeric',
                'min:0'
            ]
        ];
    }
}
