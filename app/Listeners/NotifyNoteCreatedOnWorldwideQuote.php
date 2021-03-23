<?php


namespace App\Listeners;


use App\Events\WorldwideQuote\WorldwideQuoteNoteCreated;
use App\Notifications\WorldwideQuote\NoteCreated;
use Illuminate\Support\Str;

class NotifyNoteCreatedOnWorldwideQuote
{
    /**
     * Handle the event.
     *
     * @param WorldwideQuoteNoteCreated $event
     * @return bool
     */
    public function handle(WorldwideQuoteNoteCreated $event): bool
    {
        $note = $event->getWorldwideQuoteNote();
        $quote = $note->worldwideQuote;

        $message = sprintf(
            'A new note created on the Worldwide Quote RFQ %s "%s"',
            $quote->quote_number, Str::limit(strip_tags($note->text), 50)
        );

        notification()
            ->for($quote->user)
            ->message($message)
            ->subject($quote)
            ->priority(1)
            ->store();

        $quote->user->notify(new NoteCreated(
            $quote->quote_number, $note->text
        ));

        return true;
    }
}
