<?php

namespace App\Http\Requests\Quote;

use App\Models\Quote\Quote;
use App\Models\Template\QuoteTemplate;
use App\Models\Template\TemplateField;
use Illuminate\Foundation\Http\FormRequest;

class ShowQuoteState extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    public function loadQuoteAttributes(Quote $quote): Quote
    {
        return tap($quote, function (Quote $quote) {
            filter($quote);

            $templateFields = TemplateField::with('templateFieldType')->orderBy('order')->get();

            $quote->activeVersionOrCurrent->quoteTemplate->setAttribute('template_fields', $templateFields);

            if ($quote->relationLoaded('contractTemplate')) {
                $quote->contractTemplate->setAttribute('template_fields', $templateFields);
            }
        });
    }
}
