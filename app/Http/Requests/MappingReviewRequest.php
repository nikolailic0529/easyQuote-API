<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MappingReviewRequest extends FormRequest
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
            'quote_id' => 'required|uuid|exists:quotes,id',
            'search' => 'string'
        ];
    }
}
