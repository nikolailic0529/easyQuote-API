<?php


namespace App\Listeners;


use App\Events\WorldwideQuote\WorldwideQuoteNoteCreated;
use App\Notifications\WorldwideQuote\NoteCreated;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\ChangesDetector;
use Illuminate\Support\Str;

class NotifyNoteCreatedOnWorldwideQuote
{

    public function __construct(protected ActivityLogger $activityLogger)
    {

    }

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
            ->push();

        $quote->user->notify(new NoteCreated(
            $quote->quote_number, $note->text
        ));

        return true;
    }
}
