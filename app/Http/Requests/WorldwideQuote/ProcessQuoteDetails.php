<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\QuoteStages\PackDetailsStage;
use App\Enum\PackQuoteStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessQuoteDetails extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'pricing_document' => [
                'bail', 'nullable', 'string', 'max:1000'
            ],
            'service_agreement_id' => [
                'bail', 'nullable', 'string', 'max:1000'
            ],
            'system_handle' => [
                'bail', 'nullable', 'string', 'max:1000'
            ],
            'additional_details' => [
                'bail', 'nullable', 'string', 'max:10000'
            ],
            'stage' => [
                'bail', 'required', Rule::in(PackQuoteStage::getLabels())
            ],
        ];
    }

    public function getStage(): PackDetailsStage
    {
        return new PackDetailsStage([
            'pricing_document' => $this->input('pricing_document') ?? '',
            'service_agreement_id' => $this->input('service_agreement_id') ?? '',
            'system_handle' => $this->input('system_handle') ?? '',
            'additional_details' => $this->input('additional_details'),
            'stage' => PackQuoteStage::getValueOfLabel($this->input('stage')),
        ]);
    }
}
