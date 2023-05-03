<?php

namespace App\Domain\Discount\Requests;

use App\Domain\Discount\Validation\Rules\UniqueDuration;

final class UpdateMultiYearDiscountRequest extends UpdateDiscountRequest
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
                'between:1,5',
                (new UniqueDuration('multi_year_discounts'))->ignore($this->multi_year),
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
