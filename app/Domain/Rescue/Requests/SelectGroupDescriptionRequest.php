<?php

namespace App\Domain\Rescue\Requests;

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
        /** @var \App\Domain\Rescue\Models\BaseQuote */
        $version = $this->route('quote')->activeVersion ?? $this->route('quote');

        return [
            '*' => ['string', 'uuid', Rule::in($version->group_description->pluck('id')->toArray())],
        ];
    }

    public function messages()
    {
        return [
            '*.in' => 'Selected group description does not exist in this quote.',
        ];
    }
}
