<?php

namespace App\Http\Requests\QuoteTemplate;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuoteTemplateRequest extends FormRequest
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
            'name' => 'string|min:2|max:80',
            'company_id' => 'string|uuid|exists:companies,id',
            'vendor_id' => 'string|uuid|exists:vendors,id',
            'countries' => 'array',
            'countries.*' => 'string|uuid|exists:countries,id'
        ];
    }
}
