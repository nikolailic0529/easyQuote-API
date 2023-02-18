<?php

namespace App\Domain\Discount\Requests;

use App\Domain\Discount\Validation\Rules\UniqueDuration;

class UpdateMultiYearDiscountRequest extends UpdateDiscountRequest
{
    public function additionalRules()
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

    public function messages()
    {
        return [
            'durations.*.duration.between' => 'The duration must be between :min and :max years.',
        ];
    }
}
