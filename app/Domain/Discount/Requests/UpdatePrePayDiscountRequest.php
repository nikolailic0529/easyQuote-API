<?php

namespace App\Domain\Discount\Requests;

use App\Domain\Discount\Validation\Rules\UniqueDuration;

final class UpdatePrePayDiscountRequest extends UpdateDiscountRequest
{
    public function additionalRules(): array
    {
        return [
            'durations' => [
                'array',
            ],
            'durations.*.duration' => [
                'required',
                'integer',
                'between:1,3',
                (new UniqueDuration('pre_pay_discounts'))->ignore($this->pre_pay),
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
