<?php

namespace App\Domain\Rescue\Listeners;

use App\Domain\Activity\Services\ActivityLogger;
use App\Domain\Activity\Services\ChangesDetector;
use App\Domain\Rescue\Contracts\WithRescueQuoteEntity;
use App\Domain\Rescue\Events\Quote\RescueQuoteDeleted;
use App\Domain\Rescue\Events\Quote\RescueQuoteExported;
use App\Domain\Rescue\Events\Quote\RescueQuoteFileExported;
use App\Domain\Rescue\Events\Quote\RescueQuoteSubmitted;
use App\Domain\Rescue\Events\Quote\RescueQuoteUnravelled;
use App\Domain\Rescue\Models\QuoteVersion;
use App\Domain\Rescue\Notifications\QuoteDeletedNotification;
use App\Domain\Rescue\Notifications\QuoteSubmittedNotification;
use App\Domain\Rescue\Notifications\QuoteUnravelledNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;

class RescueQuoteEventAuditor implements ShouldQueue
{
    public function __construct(
        protected ActivityLogger $activityLogger
    ) {
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(RescueQuoteUnravelled::class, [$this, 'handleUnravelledEvent']);
        $events->listen(RescueQuoteSubmitted::class, [$this, 'handleSubmittedEvent']);
        $events->listen(RescueQuoteDeleted::class, [$this, 'handleDeletedEvent']);
        $events->listen(RescueQuoteFileExported::class, [$this, 'handleFileExportEvent']);
        $events->listen(RescueQuoteExported::class, [$this, 'handleQuoteExportedEvent']);
    }

    public function handleDeletedEvent(WithRescueQuoteEntity $event): void
    {
        $quote = $event->getQuote();

        if ($quote->user) {
            $quote->user->notify(new QuoteDeletedNotification($quote));
        }
    }

    public function handleSubmittedEvent(WithRescueQuoteEntity $event): void
    {
        $quote = $event->getQuote();

        if ($quote->user) {
            $quote->user->notify(new QuoteSubmittedNotification($quote));
        }
    }

    public function handleUnravelledEvent(WithRescueQuoteEntity $event): void
    {
        $quote = $event->getQuote();

        if ($quote->user) {
            $quote->user->notify(new QuoteUnravelledNotification($quote));
        }
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
                    'exported_file' => sprintf('%s (%s)', $quoteFile->original_file_name, $quoteFile->file_type),
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
}
