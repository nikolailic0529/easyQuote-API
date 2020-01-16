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
            slack()
                ->title('Quote Submission')
                ->url(ui_route('quotes.drafted.review', compact('quote')))
                ->status([QSF_01, 'Quote RFQ' => $quote->rfq_number, 'Reason' => QSE_01, 'Caused By' => optional(request()->user())->fullname])
                ->image(assetExternal(SN_IMG_QSF))
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
        $rfq_number = $quote->rfq_number;
        $url = ui_route('quotes.submitted.review', compact('quote'));

        slack()
            ->title('Quote Submission')
            ->url($url)
            ->status([QSS_01, 'Quote RFQ' => $rfq_number, 'Caused By' => optional(request()->user())->fullname])
            ->image(assetExternal(SN_IMG_QSS))
            ->send();

        notification()
            ->for($quote->user)
            ->message(__(QSS_02, compact('rfq_number')))
            ->subject($quote)
            ->url($url)
            ->priority(1)
            ->queue();
    }

    /**
     * Handle the Quote "unsubmitted" event.
     *
     * @param Quote $quote
     * @return void
     */
    public function unsubmitted(Quote $quote)
    {
        $url = ui_route('quotes.drafted.review', compact('quote'));
        $rfq_number = $quote->rfq_number;

        notification()
            ->for($quote->user)
            ->message(__(QDS_01, compact('rfq_number')))
            ->subject($quote)
            ->url($url)
            ->priority(1)
            ->queue();
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
