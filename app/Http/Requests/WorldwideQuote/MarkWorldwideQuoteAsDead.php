<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\WorldwideQuote\MarkWorldwideQuoteAsDeadData;
use Illuminate\Foundation\Http\FormRequest;

class MarkWorldwideQuoteAsDead extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'status_reason' => [
                'bail', 'required', 'string', 'max:500'
            ]
        ];
    }

    public function getMarkQuoteAsDeadData(): MarkWorldwideQuoteAsDeadData
    {
        return new MarkWorldwideQuoteAsDeadData([
            'status_reason' => $this->input('status_reason')
        ]);
    }
}
