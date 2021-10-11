<?php

namespace App\Listeners;

use App\Contracts\WithRescueQuoteEntity;
use App\Events\RescueQuote\RescueQuoteDeleted;
use App\Events\RescueQuote\RescueQuoteExported;
use App\Events\RescueQuote\RescueQuoteFileExported;
use App\Events\RescueQuote\RescueQuoteNoteCreated;
use App\Events\RescueQuote\RescueQuoteSubmitted;
use App\Events\RescueQuote\RescueQuoteUnravelled;
use App\Models\Quote\QuoteVersion;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\ChangesDetector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;

class RescueQuoteEventAuditor implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(protected ActivityLogger $activityLogger)
    {
        //
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(RescueQuoteUnravelled::class, [$this, 'handleUnravelledEvent']);
        $events->listen(RescueQuoteSubmitted::class, [$this, 'handleSubmittedEvent']);
        $events->listen(RescueQuoteDeleted::class, [$this, 'handleDeletedEvent']);
        $events->listen(RescueQuoteFileExported::class, [$this, 'handleFileExportEvent']);
        $events->listen(RescueQuoteNoteCreated::class, [$this, 'handleNoteCreatedEvent']);
        $events->listen(RescueQuoteExported::class, [$this, 'handleQuoteExportedEvent']);
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

    public function handleFileExportEvent(RescueQuoteFileExported $event)
    {
        $quote = $event->getParentEntity();
        $quoteFile = $event->getQuoteFile();

        $this->activityLogger
            ->on($quote)
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [
                ],
                ChangesDetector::NEW_ATTRS_KEY => [
                    'exported_file' => sprintf("%s (%s)", $quoteFile->original_file_name, $quoteFile->file_type),
                ],
            ])
            ->log('exported');
    }

    public function handleQuoteExportedEvent(RescueQuoteExported $event)
    {
        $quote = $event->getQuote() instanceof QuoteVersion
            ? $event->getQuote()->quote
            : $event->getQuote();

        $asType = $event->getAsType();

        $this->activityLogger
            ->on($quote)
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [],
                ChangesDetector::NEW_ATTRS_KEY => [
                    'exported_file' => match ($asType) {
                        QT_TYPE_QUOTE => 'Quote as PDF',
                        QT_TYPE_CONTRACT => 'Contract as PDF',
                        QT_TYPE_HPE_CONTRACT => 'HPE Contract as PDF',
                    },
                ],
            ])
            ->log('exported');
    }

    public function handleNoteCreatedEvent(RescueQuoteNoteCreated $event)
    {
        $quoteNote = $event->getQuoteNote();

        $this->activityLogger
            ->on($quoteNote->quote)
            ->by($quoteNote->user ?? $quoteNote->quote->user)
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [],
                ChangesDetector::NEW_ATTRS_KEY => [
                    'new_notes' => $quoteNote->text,
                ],
            ])
            ->log('updated');
    }
}
