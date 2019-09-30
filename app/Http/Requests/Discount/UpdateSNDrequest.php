<?php namespace App\Http\Requests\Discount;

class UpdateSNDrequest extends UpdateDiscountRequest
{
    public function additionalRules()
    {
        return [
            'value' => [
                'numeric',
                'min:0'
            ]
        ];
    }
}
