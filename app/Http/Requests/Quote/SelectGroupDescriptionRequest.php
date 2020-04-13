<?php

namespace App\Http\Requests\Quote;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Arr;

class SelectGroupDescriptionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $version = $this->route('quote')->usingVersion;

        return [
            '*' => ['string', 'uuid', Rule::in(Arr::pluck($version->group_description, 'id'))]
        ];
    }

    public function messages()
    {
        return [
            '*.in' => 'Selected group description does not exist in this quote.'
        ];
    }
}
