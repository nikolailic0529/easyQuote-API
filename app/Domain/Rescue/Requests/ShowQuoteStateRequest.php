<?php

namespace App\Domain\Rescue\Requests;

use App\Domain\Rescue\Models\Quote;
use App\Domain\Rescue\Queries\QuoteQueries;
use App\Domain\Rescue\Services\RescueQuoteCalc;
use App\Domain\Template\Models\TemplateField;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Foundation\Http\FormRequest;

class ShowQuoteStateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
        ];
    }

    public function loadQuoteAttributes(Quote $quote): Quote
    {
        return tap($quote, function (Quote $quote) {
            filter($quote);

            $config = $this->container[Config::class];

            $quoteQueries = $this->container[QuoteQueries::class];

            /** @var RescueQuoteCalc $quoteCalc */
            $quoteCalc = $this->container[RescueQuoteCalc::class];

            $quote->activeVersionOrCurrent->totalPrice = $quoteCalc->calculateListPriceOfRescueQuote($quote->activeVersionOrCurrent);

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
