<?php

namespace App\Http\Requests\Quote;

use App\Models\Quote\Quote;
use App\Services\QuoteQueries;
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

    public function includeModelAttributes(Quote $quote): Quote
    {
        $quoteQueries = $this->container[QuoteQueries::class];

        $quote->activeVersionOrCurrent->totalPrice = (float)$quoteQueries->mappedSelectedRowsQuery($quote->activeVersionOrCurrent)->sum('price');

        return $quote;
    }
}
