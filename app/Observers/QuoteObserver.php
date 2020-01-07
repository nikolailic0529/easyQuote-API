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
        if ($this->sameRfqSubmittedQuoteExists($quote)) {
            slack_client()
                ->title('Quote Submission')
                ->url(ui_route('quotes.drafted.review', ['quote_id' => $quote->id]))
                ->status([QSF_01, 'Quote RFQ' => $quote->rfqNumber, 'Reason' => QSE_01, 'Caused By' => optional(request()->user())->fullname])
                ->image('https://media1.tenor.com/images/2818749911c12b7f854d45e250c0b6d1/tenor.gif')
                ->send();

            error_abort(QSE_01, 'QSE_01', 409);
        }
    }

    /**
     * Handle the Quote "submitted" event.
     *
     * @param Quote $quote
     * @return void
     */
    public function submitted(Quote $quote)
    {
        slack_client()
            ->title('Quote Submission')
            ->url(ui_route('quotes.submitted.review', ['quote_id' => $quote->id]))
            ->status([QSS_01, 'Quote RFQ' => $quote->rfqNumber, 'Caused By' => optional(request()->user())->fullname])
            ->image('https://media.giphy.com/media/S9iMXx6Lqrr5mJoMxC/giphy.gif')
            ->send();
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
