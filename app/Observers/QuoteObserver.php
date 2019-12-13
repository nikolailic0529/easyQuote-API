<?php

namespace App\Observers;

use App\Models\Quote\Quote;

class QuoteObserver
{
    /**
     * Handle the Quote "activating" event.
     *
     * @param Quote $quote
     * @return void
     */
    public function activating(Quote $quote)
    {
        if (!$quote->isSubmitted()) {
            return;
        }

        abort_if($this->sameRfqSubmittedQuoteExists($quote), 409, __('quote.exists_same_rfq_submitted_quote'));
    }

    /**
     * Handle the Quote "submitting" event.
     *
     * @param Quote $quote
     * @return void
     */
    public function submitting(Quote $quote)
    {
        abort_if($this->sameRfqSubmittedQuoteExists($quote), 409, __('quote.exists_same_rfq_submitted_quote'));
    }

    /**
     * Handle the Quote "deleting" event.
     *
     * @param  \App\Models\Quote\Quote  $quote
     * @return void
     */
    public function deleting(Quote $quote)
    {
        return $quote->countryMargin()->dissociate();
    }

    protected function sameRfqSubmittedQuoteExists(Quote $quote)
    {
        return $quote->query()
            ->submitted()
            ->activated()
            ->where('id', '!=', $quote->id)
            ->rfq($quote->customer->rfq)
            ->exists();
    }
}
