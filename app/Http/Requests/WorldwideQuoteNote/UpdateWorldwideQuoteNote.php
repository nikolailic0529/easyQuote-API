<?php

namespace App\Http\Requests\WorldwideQuoteNote;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorldwideQuoteNote extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'text' => ['required', 'string', 'max:20000']
        ];
    }
}
