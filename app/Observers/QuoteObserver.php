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

        error_abort_if($this->sameRfqSubmittedQuoteExists($quote), QSE_01, 'QSE_01', 409);
    }

    /**
     * Handle the Quote "submitting" event.
     *
     * @param Quote $quote
     * @return void
     */
    public function submitting(Quote $quote)
    {
        error_abort_if($this->sameRfqSubmittedQuoteExists($quote), QSE_01, 'QSE_01', 409);
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
