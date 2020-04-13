<?php

namespace App\Listeners;

use App\Events\QuoteNoteCreated;
use App\Notifications\QuoteNoteCreated as CreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

class QuoteNoteCreatedListener implements ShouldQueue
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

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(QuoteNoteCreated $event)
    {
        $causer = $event->quoteNote->user;
        $quote = $event->quoteNote->quote;

        if ($causer->is($quote->user)) {
            report_logger(['message' => 'Quote note causer is quote user. The user will not be notified.']);
            return;
        }

        $message = sprintf(
            'User %s created a new note on the Quote RFQ %s "%s"',
            $causer->email, $quote->customer->rfq, Str::limit(strip_tags($event->quoteNote->text), 50)
        );

        notification()
            ->for($quote->user)
            ->message($message)
            ->subject($quote)
            ->url(ui_route('quotes.status', ['quote' => $quote]))
            ->priority(1)
            ->store();

        $quote->user->notify(new CreatedNotification($event->quoteNote));
    }
}
