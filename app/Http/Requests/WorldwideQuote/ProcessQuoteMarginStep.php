<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\QuoteStages\PackMarginTaxStage;
use App\Enum\PackQuoteStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessQuoteMarginStep extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'tax_value' => [
                'bail', 'nullable', 'numeric', 'min:'.(-100_000_000), 'max:'.(100_000_000),
            ],
            'margin_value' => [
                'bail', 'nullable', 'numeric', 'min:'.(-100_000_000), 'max:'.(100_000_000),
            ],
            'quote_type' => [
                'bail', 'required_with:margin_value', 'nullable', 'string', 'in:New,Renewal'
            ],
            'margin_method' => [
                'bail', 'required_with:margin_value', 'nullable', 'string', 'in:No Margin,Standard'
            ],
            'stage' => [
                'bail', 'required', Rule::in(PackQuoteStage::getLabels())
            ],
        ];
    }

    public function getStage(): PackMarginTaxStage
    {
        return new PackMarginTaxStage([
            'tax_value' => transform($this->input('tax_value'), fn($value) => (float)$value),
            'margin_value' => transform($this->input('margin_value'), fn($value) => (float)$value),
            'quote_type' => $this->input('quote_type') ?? 'New',
            'margin_method' => $this->input('margin_method') ?? 'No Margin',
            'stage' => PackQuoteStage::getValueOfLabel($this->input('stage'))
        ]);
    }
}
