<?php

namespace App\Http\Requests\Quote;

use Illuminate\Foundation\Http\FormRequest;

class TryDiscountsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            '*.id' => [
                'required',
                'uuid',
                'exists:discounts,discountable_id'
            ],
            '*.duration' => [
                'integer',
                'min:0'
            ]
        ];
    }
}
