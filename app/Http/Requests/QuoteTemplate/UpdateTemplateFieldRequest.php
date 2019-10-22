<?php namespace App\Http\Requests\QuoteTemplate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTemplateFieldRequest extends FormRequest
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
            'header' => [
                'string',
                'min:2',
                'max:50'
            ],
            'default_value' => 'nullable|string|min:1|max:250',
            'template_field_type_id' => 'string|uuid|exists:template_field_types,id'
        ];
    }
}
