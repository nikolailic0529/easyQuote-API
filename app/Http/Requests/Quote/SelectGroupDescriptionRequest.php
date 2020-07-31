<?php

namespace App\Http\Requests\Quote;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class SelectGroupDescriptionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        /** @var \App\Models\Quote\BaseQuote */
        $version = $this->route('quote')->usingVersion;

        return [
            '*' => ['string', 'uuid', Rule::in($version->group_description->pluck('id')->toArray())]
        ];
    }

    public function messages()
    {
        return [
            '*.in' => 'Selected group description does not exist in this quote.'
        ];
    }
}
