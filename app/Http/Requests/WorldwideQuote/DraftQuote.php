<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\QuoteStages\DraftStage;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class DraftQuote extends FormRequest
{
    protected ?DraftStage $draftStage = null;

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

    public function getStage(): DraftStage
    {
        return $this->draftStage ??= new DraftStage([
            'quote_closing_date' => Carbon::createFromFormat('Y-m-d', $this->input('quote_closing_date')),
            'additional_notes' => $this->input('additional_notes'),
        ]);
    }
}
