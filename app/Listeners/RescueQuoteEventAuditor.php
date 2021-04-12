<?php

namespace App\Listeners;

use App\Contracts\WithRescueQuoteEntity;
use App\Events\RescueQuote\RescueQuoteDeleted;
use App\Events\RescueQuote\RescueQuoteSubmitted;
use App\Events\RescueQuote\RescueQuoteUnravelled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;

class RescueQuoteEventAuditor implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(RescueQuoteUnravelled::class, [$this, 'handleUnravelledEvent']);
        $events->listen(RescueQuoteSubmitted::class, [$this, 'handleSubmittedEvent']);
        $events->listen(RescueQuoteDeleted::class, [$this, 'handleDeletedEvent']);
    }

    /**
     * @param WithRescueQuoteEntity $event
     * @return void
     */
    public function handleDeletedEvent(WithRescueQuoteEntity $event)
    {
        $quote = $event->getQuote();

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
     * @param WithRescueQuoteEntity $event
     * @return void
     */
    public function handleSubmittedEvent(WithRescueQuoteEntity $event)
    {
        $quote = $event->getQuote();

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
     * @param WithRescueQuoteEntity $event
     * @return void
     */
    public function handleUnravelledEvent(WithRescueQuoteEntity $event)
    {
        $quote = $event->getQuote();

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
