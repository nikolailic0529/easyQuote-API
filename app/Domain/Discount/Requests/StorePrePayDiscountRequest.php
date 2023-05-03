<?php

namespace App\Domain\Discount\Requests;

use App\Domain\Discount\Validation\Rules\UniqueDuration;

final class StorePrePayDiscountRequest extends StoreDiscountRequest
{
    public function additionalRules(): array
    {
        return [
            'durations' => [
                'required',
                'array',
            ],
            'durations.*.duration' => [
                'required',
                'integer',
                'between:1,3',
                new UniqueDuration('pre_pay_discounts'),
            ],
            'durations.*.value' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'durations.*.duration.between' => __('discount.validation.duration_between'),
        ]);
    }
}
