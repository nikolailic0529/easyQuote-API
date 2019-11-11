<?php namespace App\Http\Requests\Quote;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupDescriptionRequest extends FormRequest
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
            'name' => 'required|string|min:1',
            'search_text' => 'required|string|min:1',
            'rows' => 'required|array',
            'rows.*' => 'required|string|uuid|exists:imported_rows,id'
        ];
    }
}
