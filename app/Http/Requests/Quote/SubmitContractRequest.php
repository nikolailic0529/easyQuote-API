<?php

namespace App\Http\Requests\Quote;

use Illuminate\Foundation\Http\FormRequest;

class SubmitContractRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'closing_date' => 'required|date_format:Y-m-d',
            'additional_notes' => 'required|string|max:20000|min:2'
        ];
    }

    public function validated()
    {
        $closing_date = now()->format('Y-m-d');

        return parent::validated() + compact('closing_date');
    }
}
