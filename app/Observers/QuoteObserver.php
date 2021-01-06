<?php

namespace App\Observers;

use App\Models\Quote\Quote;

class QuoteObserver
{
    /**
     * Handle the Quote "created" event.
     *
     * @param \App\Models\Quote\Quote $quote
     * @return void
     */
    public function created(Quote $quote)
    {
        app('customer.repository')->flushListingCache();
    }

    /**
     * Handle the Quote "deleted" event.
     *
     * @param \App\Models\Quote\Quote $quote
     * @return void
     */
    public function deleted(Quote $quote)
    {
        app('customer.repository')->flushListingCache();

        $rfq_number = $quote->rfq_number;

        notification()
            ->for($quote->user)
            ->message(__(QD_01, compact('rfq_number')))
            ->subject($quote)
            ->url(ui_route('users.notifications'))
            ->priority(3)
            ->queue();
    }

    /**
     * Handle the Quote "submitted" event.
     *
     * @param \App\Models\Quote\Quote $quote
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
            ->queue();

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
     * @param \App\Models\Quote\Quote $quote
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
}
