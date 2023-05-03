<?php

namespace App\Domain\Rescue\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupDescriptionRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'min:1',
                Rule::notIn(collect($this->quote->group_description)->pluck('name')),
            ],
            'search_text' => 'required|string|min:1',
            'rows' => 'required|array',
            'rows.*' => 'required|string|uuid|distinct|exists:imported_rows,id',
        ];
    }

    public function messages()
    {
        return [
            'name.not_in' => 'The selected Group name is already taken.',
        ];
    }
}
