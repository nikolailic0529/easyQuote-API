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
                ->status([QSF_01, 'Quote RFQ' => $quote->rfqNumber, 'Reason' => QSE_01, 'Caused By' => optional(request()->user())->fullname])
                ->image(assetExternal('img/qsf.gif'))
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
            ->status([QSS_01, 'Quote RFQ' => $quote->rfqNumber, 'Caused By' => optional(request()->user())->fullname])
            ->image(assetExternal('img/qss.gif'))
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
