<?php namespace App\Http\Requests\QuoteTemplate;

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
            'currency_id' => 'nullable|string|uuid|exists:currencies,id',
            'countries' => 'array',
            'countries.*' => 'string|uuid|exists:countries,id',
            'form_data' => 'array',
            'form_values_data' => 'required_with:form_data|array',
            'complete_design' => 'boolean'
        ];
    }
}
