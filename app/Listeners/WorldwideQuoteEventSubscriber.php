<?php

namespace App\Listeners;

use App\Enum\ContractQuoteStage;
use App\Enum\PackQuoteStage;
use App\Events\WorldwideQuote\WorldwideContractQuoteDetailsStepProcessed;
use App\Events\WorldwideQuote\WorldwideContractQuoteDiscountStepProcessed;
use App\Events\WorldwideQuote\WorldwideContractQuoteImportStepProcessed;
use App\Events\WorldwideQuote\WorldwideContractQuoteMappingReviewStepProcessed;
use App\Events\WorldwideQuote\WorldwideContractQuoteMappingStepProcessed;
use App\Events\WorldwideQuote\WorldwidePackQuoteAssetsCreationStepProcessed;
use App\Events\WorldwideQuote\WorldwidePackQuoteAssetsReviewStepProcessed;
use App\Events\WorldwideQuote\WorldwidePackQuoteContactsStepProcessed;
use App\Events\WorldwideQuote\WorldwidePackQuoteDetailsStepProcessed;
use App\Events\WorldwideQuote\WorldwidePackQuoteDiscountStepProcessed;
use App\Events\WorldwideQuote\WorldwidePackQuoteMarginStepProcessed;
use App\Events\WorldwideQuote\WorldwideQuoteDeleted;
use App\Events\WorldwideQuote\WorldwideQuoteDrafted;
use App\Events\WorldwideQuote\WorldwideQuoteInitialized;
use App\Events\WorldwideQuote\WorldwideQuoteSubmitted;
use App\Events\WorldwideQuote\WorldwideQuoteUnraveled;
use App\Models\Quote\WorldwideQuote;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\ChangesDetector;
use Closure;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

class WorldwideQuoteEventSubscriber
{
    protected BusDispatcher $busDispatcher;

    protected ActivityLogger $activityLogger;

    protected ChangesDetector $changesDetector;

    public function __construct(BusDispatcher $busDispatcher, ActivityLogger $activityLogger, ChangesDetector $changesDetector)
    {
        $this->busDispatcher = $busDispatcher;
        $this->activityLogger = $activityLogger;
        $this->changesDetector = $changesDetector;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe(\Illuminate\Events\Dispatcher $events)
    {
        $events->listen(WorldwideQuoteInitialized::class, Closure::fromCallable([$this, 'handleInitializedEvent']));
        $events->listen(WorldwideQuoteSubmitted::class, Closure::fromCallable([$this, 'handleSubmittedEvent']));
        $events->listen(WorldwideQuoteUnraveled::class, Closure::fromCallable([$this, 'handleUnraveledEvent']));
        $events->listen(WorldwideQuoteDrafted::class, Closure::fromCallable([$this, 'handleDraftedEvent']));
        $events->listen(WorldwideQuoteDeleted::class, Closure::fromCallable([$this, 'handleDeletedEvent']));

        $events->listen(WorldwideContractQuoteDetailsStepProcessed::class, Closure::fromCallable([$this, 'handleContractQuoteDetailsStepProcessedEvent']));
        $events->listen(WorldwideContractQuoteDiscountStepProcessed::class, Closure::fromCallable([$this, 'handleContractQuoteDiscountStepProcessedEvent']));
        $events->listen(WorldwideContractQuoteImportStepProcessed::class, Closure::fromCallable([$this, 'handleContractQuoteImportStepProcessedEvent']));
        $events->listen(WorldwideContractQuoteMappingStepProcessed::class, Closure::fromCallable([$this, 'handleContractQuoteMappingStepProcessedEvent']));
        $events->listen(WorldwideContractQuoteMappingReviewStepProcessed::class, Closure::fromCallable([$this, 'handleContractQuoteMappingStepProcessedEvent']));
        $events->listen(WorldwidePackQuoteAssetsCreationStepProcessed::class, Closure::fromCallable([$this, 'handlePackQuoteAssetsCreationStepProcessedEvent']));
        $events->listen(WorldwidePackQuoteAssetsReviewStepProcessed::class, Closure::fromCallable([$this, 'handlePackQuoteAssetsReviewStepProcessedEvent']));
        $events->listen(WorldwidePackQuoteContactsStepProcessed::class, Closure::fromCallable([$this, 'handlePackQuoteContactsStepProcessedEvent']));
        $events->listen(WorldwidePackQuoteDetailsStepProcessed::class, Closure::fromCallable([$this, 'handlePackQuoteDetailsStepProcessedEvent']));
        $events->listen(WorldwidePackQuoteDiscountStepProcessed::class, Closure::fromCallable([$this, 'handlePackQuoteDiscountStepProcessedEvent']));
        $events->listen(WorldwidePackQuoteMarginStepProcessed::class, Closure::fromCallable([$this, 'handlePackQuoteMarginStepProcessedEvent']));
    }

    private function getQuoteStage(WorldwideQuote $quote): ?string
    {
        if ($quote->contract_type_id === CT_PACK) {
            return PackQuoteStage::getLabelOfValue($quote->completeness);
        }

        if ($quote->contract_type_id === CT_CONTRACT) {
            return ContractQuoteStage::getLabelOfValue($quote->completeness);
        }

        return null;
    }

    public function handleInitializedEvent(WorldwideQuoteInitialized $event)
    {
        $quote = $event->getQuote();

        $this->activityLogger
            ->performedOn($quote)
            ->causedBy($quote->user)
            ->withProperties([
                'old' => [],
                'attributes' => [
                    'contract_type' => $quote->contractType->type_short_name,
                    'project_name' => $quote->opportunity->project_name,
                    'stage' => $this->getQuoteStage($quote),
                    'quote_number' => $quote->quote_number,
                    'quote_expiry_date' => $quote->quote_expiry_date,
                ]
            ])
            ->log('created');
    }

    public function handleSubmittedEvent(WorldwideQuoteSubmitted $event)
    {
        //
    }

    public function handleUnraveledEvent(WorldwideQuoteUnraveled $event)
    {
        //
    }

    public function handleDraftedEvent(WorldwideQuoteDrafted $event)
    {
        //
    }

    public function handleDeletedEvent(WorldwideQuoteDeleted $event)
    {
        //
    }

    public function handleContractQuoteDetailsStepProcessedEvent(WorldwideContractQuoteDetailsStepProcessed $event)
    {
        //
    }

    public function handleContractQuoteDiscountStepProcessedEvent(WorldwideContractQuoteDiscountStepProcessed $event)
    {
        //
    }

    public function handleContractQuoteImportStepProcessedEvent(WorldwideContractQuoteImportStepProcessed $event)
    {
        //
    }

    public function handleContractQuoteMappingStepProcessedEvent(WorldwideContractQuoteMappingStepProcessed $event)
    {
        //
    }

    public function handleContractQuoteMappingReviewStepProcessedEvent(WorldwideContractQuoteMappingReviewStepProcessed $event)
    {
        //
    }

    public function handlePackQuoteAssetsCreationStepProcessedEvent(WorldwidePackQuoteAssetsCreationStepProcessed $event)
    {
        //
    }

    public function handlePackQuoteAssetsReviewStepProcessedEvent(WorldwidePackQuoteAssetsReviewStepProcessed $event)
    {
        //
    }

    public function handlePackQuoteContactsStepProcessedEvent(WorldwidePackQuoteContactsStepProcessed $event)
    {
        //
    }

    public function handlePackQuoteDetailsStepProcessedEvent(WorldwidePackQuoteDetailsStepProcessed $event)
    {
        //
    }

    public function handlePackQuoteDiscountStepProcessedEvent(WorldwidePackQuoteDiscountStepProcessed $event)
    {
        //
    }

    public function handlePackQuoteMarginStepProcessedEvent(WorldwidePackQuoteMarginStepProcessed $event)
    {
        //
    }
}
