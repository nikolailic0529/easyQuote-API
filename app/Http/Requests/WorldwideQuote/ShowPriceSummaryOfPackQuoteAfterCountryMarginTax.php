<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\Margin\ImmutableMarginTaxData;
use App\DTO\Margin\MarginTaxData;
use Illuminate\Foundation\Http\FormRequest;

class ShowPriceSummaryOfPackQuoteAfterCountryMarginTax extends FormRequest
{
    protected ?ImmutableMarginTaxData $marginTaxData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'margin_value' => [
                'bail', 'nullable', 'numeric', 'min:'.(-100_000_000), 'max:'.(100_000_000),
            ],
            'tax_value' => [
                'bail', 'nullable', 'numeric', 'min:'.(-100_000_000), 'max:'.(100_000_000),
            ],
        ];
    }

    public function getMarginTaxData(): ImmutableMarginTaxData
    {
        return $this->marginTaxData ??= MarginTaxData::immutable([
            'margin_value' => transform($this->input('margin_value'), fn($value) => (float)$value),
            'tax_value' => transform($this->input('tax_value'), fn($value) => (float)$value)
        ]);
    }
}
