<?php

namespace App\Services;

use App\Contracts\Repositories\Quote\QuoteDraftedRepositoryInterface as DraftedQuotes;
use App\Contracts\Repositories\Quote\QuoteSubmittedRepositoryInterface as SubmittedQuotes;
use App\Contracts\Services\QuoteServiceInterface as QuoteService;
use App\Models\Quote\Quote;
use App\Models\Quote\QuoteTotal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class StatsService
{
    protected QuoteTotal $total;

    protected QuoteService $quoteService;

    protected DraftedQuotes $draftedQuotes;

    protected SubmittedQuotes $submittedQuotes;

    public function __construct(
        QuoteTotal $total,
        QuoteService $quoteService,
        DraftedQuotes $draftedQuotes,
        SubmittedQuotes $submittedQuotes
    ) {
        $this->total = $total;
        $this->quoteService = $quoteService;
        $this->draftedQuotes = $draftedQuotes;
        $this->submittedQuotes = $submittedQuotes;
    }

    public function calculateQuotesTotals()
    {
        $this->draftedQuotes->cursor(
            fn (Builder $q) => $q->with('countryMargin', 'usingVersion')
        )
            ->each(fn (Quote $quote) => $this->handleQuote($quote));

        $this->submittedQuotes->cursor(
            fn (Builder $q) => $q->with('countryMargin', 'usingVersion')
        )
            ->each(fn (Quote $quote) => $this->handleQuote($quote));
    }

    protected function handleQuote(Quote $quote): void
    {
        $version = $quote->usingVersion;

        $totalPrice = $version->totalPrice / $version->margin_divider * $version->base_exchange_rate;

        $attributes = [
            'quote_id'              => $quote->id,
            'total_price'           => $totalPrice,
            'rfq_number'            => $quote->customer->rfq,
            'quote_created_at'      => $quote->getRawOriginal('created_at'),
            'quote_submitted_at'    => $quote->getRawOriginal('submitted_at'),
            'valid_until_date'      => $quote->customer->getRawOriginal('valid_until'),
        ];

        $this->total->query()->updateOrCreate(Arr::only($attributes, 'quote_id'), $attributes);

        report_logger(['message' => sprintf(
            'Quote RFQ %s has been calculated. Total price %s',
            $quote->customer->rfq,
            $totalPrice
        )]);
    }
}
