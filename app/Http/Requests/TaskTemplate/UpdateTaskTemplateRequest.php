<?php

namespace App\Http\Requests\TaskTemplate;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return optional(auth()->user())->hasRole('Administrator');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'form_data' => 'required|array'
        ];
    }
}
