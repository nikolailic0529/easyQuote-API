<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Worldwide\DataTransferObjects\QuoteStages\SubmitStage;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class SubmitQuoteRequest extends FormRequest
{
    protected ?SubmitStage $submitStage = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'quote_closing_date' => [
                'bail', 'required', 'date_format:Y-m-d',
            ],
            'additional_notes' => [
                'bail', 'nullable', 'string', 'max:10000',
            ],
        ];
    }

    public function getStage(): SubmitStage
    {
        return $this->submitStage ??= new SubmitStage([
           'quote_closing_date' => Carbon::createFromFormat('Y-m-d', $this->input('quote_closing_date')),
           'additional_notes' => $this->input('additional_notes'),
        ]);
    }
}
