<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Worldwide\DataTransferObjects\Quote\MarkWorldwideQuoteAsDeadData;
use Illuminate\Foundation\Http\FormRequest;

class MarkWorldwideQuoteAsDeadRequest extends FormRequest
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
                'bail', 'required', 'string', 'max:500',
            ],
        ];
    }

    public function getMarkQuoteAsDeadData(): MarkWorldwideQuoteAsDeadData
    {
        return new MarkWorldwideQuoteAsDeadData([
            'status_reason' => $this->input('status_reason'),
        ]);
    }
}
