<?php

namespace App\Http\Requests\Quote;

use App\Models\Quote\Quote;
use App\Models\Template\QuoteTemplate;
use App\Models\Template\TemplateField;
use App\Queries\QuoteQueries;
use Illuminate\Contracts\Config\Repository as Config;
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

            $config = $this->container[Config::class];

            $quoteQueries = $this->container[QuoteQueries::class];

            $quote->activeVersionOrCurrent->totalPrice = (float)$quoteQueries->mappedSelectedRowsQuery($quote->activeVersionOrCurrent)->sum('price');

            $templateFields = TemplateField::with('templateFieldType')
                ->whereIn('name', $config['quote-mapping.rescue_quote.fields'] ?? [])
                ->orderBy('order')
                ->get();

            $quote->activeVersionOrCurrent->setAttribute('template_fields', $templateFields);

            $quote->activeVersionOrCurrent->quoteTemplate->setAttribute('template_fields', $templateFields);

            if ($quote->relationLoaded('contractTemplate')) {
                $quote->contractTemplate->setAttribute('template_fields', $templateFields);
            }
        });
    }
}
