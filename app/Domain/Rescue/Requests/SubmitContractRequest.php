<?php

namespace App\Domain\Rescue\Requests;

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
            'additional_notes' => 'string|max:20000',
        ];
    }

    public function validated()
    {
        $closing_date = now()->format('Y-m-d');

        return compact('closing_date') + parent::validated();
    }
}
