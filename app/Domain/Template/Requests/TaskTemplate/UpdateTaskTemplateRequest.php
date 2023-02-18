<?php

namespace App\Domain\Template\Requests\TaskTemplate;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskTemplateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'form_data' => 'required|array',
        ];
    }
}
