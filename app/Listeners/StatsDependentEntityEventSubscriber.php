<?php

namespace App\Listeners;

use App\Contracts\WithAssetEntity;
use App\Contracts\WithOpportunityEntity;
use App\Contracts\WithRescueQuoteEntity;
use App\Contracts\WithWorldwideQuoteEntity;
use App\Events\Asset\AssetCreated;
use App\Events\Asset\AssetDeleted;
use App\Events\Asset\AssetUpdated;
use App\Events\Opportunity\{OpportunityCreated,
    OpportunityDeleted,
    OpportunityMarkedAsLost,
    OpportunityMarkedAsNotLost,
    OpportunityUpdated};
use App\Events\RescueQuote\RescueQuoteCreated;
use App\Events\RescueQuote\RescueQuoteDeleted;
use App\Events\RescueQuote\RescueQuoteSubmitted;
use App\Events\RescueQuote\RescueQuoteUnravelled;
use App\Events\RescueQuote\RescueQuoteUpdated;
use App\Events\WorldwideQuote\{WorldwideContractQuoteDiscountStepProcessed,
    WorldwideContractQuoteImportStepProcessed,
    WorldwideContractQuoteMappingReviewStepProcessed,
    WorldwideContractQuoteMappingStepProcessed,
    WorldwideContractQuoteMarginStepProcessed,
    WorldwidePackQuoteAssetsCreationStepProcessed,
    WorldwidePackQuoteAssetsReviewStepProcessed,
    WorldwidePackQuoteDiscountStepProcessed,
    WorldwidePackQuoteMarginStepProcessed,
    WorldwideQuoteDeleted,
    WorldwideQuoteInitialized,
    WorldwideQuoteMarkedAsAlive,
    WorldwideQuoteMarkedAsDead,
    WorldwideQuoteSubmitted,
    WorldwideQuoteUnraveled};
use App\Models\Quote\QuoteTotal;
use App\Services\Stats\StatsCalculationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher as EventDispatcher;

class StatsDependentEntityEventSubscriber implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @param StatsCalculationService $statsCalculationService
     */
    public function __construct(protected StatsCalculationService $statsCalculationService)
    {
    }

    /**
     * @param EventDispatcher $events
     */
    public function subscribe(EventDispatcher $events)
    {
        $events->listen([
            WorldwideQuoteInitialized::class,
            WorldwideQuoteMarkedAsAlive::class,
            WorldwideQuoteMarkedAsDead::class,
            WorldwideQuoteSubmitted::class,
            WorldwideQuoteUnraveled::class,

            WorldwideContractQuoteDiscountStepProcessed::class,
            WorldwideContractQuoteImportStepProcessed::class,
            WorldwideContractQuoteMappingReviewStepProcessed::class,
            WorldwideContractQuoteMappingStepProcessed::class,
            WorldwideContractQuoteMarginStepProcessed::class,

            WorldwidePackQuoteAssetsCreationStepProcessed::class,
            WorldwidePackQuoteAssetsReviewStepProcessed::class,
            WorldwidePackQuoteAssetsCreationStepProcessed::class,
            WorldwidePackQuoteDiscountStepProcessed::class,
            WorldwidePackQuoteMarginStepProcessed::class,
        ], [$this, 'updateAggregationOfWorldwideQuote']);

        $events->listen([
            WorldwideQuoteDeleted::class,
        ], [$this, 'deleteAggregationOfWorldwideQuote']);


        $events->listen([
            OpportunityCreated::class,
            OpportunityUpdated::class,
            OpportunityMarkedAsLost::class,
            OpportunityMarkedAsNotLost::class,
        ], [$this, 'updateAggregationOfOpportunity']);

        $events->listen(OpportunityDeleted::class, [$this, 'deleteAggregationOfOpportunity']);

        $events->listen([
            RescueQuoteCreated::class,
            RescueQuoteUpdated::class,
            RescueQuoteSubmitted::class,
            RescueQuoteUnravelled::class,
        ], [$this, 'updateAggregationOfRescueQuote']);

        $events->listen(RescueQuoteDeleted::class, [$this, 'deleteAggregationOfRescueQuote']);

        $events->listen([
            AssetCreated::class,
            AssetUpdated::class,
            AssetDeleted::class,
        ], [$this, 'updateAggregationOfAsset']);
    }

    public function updateAggregationOfAsset(WithAssetEntity $event)
    {
        $asset = $event->getAsset();

        if (!is_null($asset->location)) {
            $this->statsCalculationService->denormalizeSummaryOfAsset($asset->location);
        }
    }

    public function updateAggregationOfRescueQuote(WithRescueQuoteEntity $event)
    {
        $quote = $event->getQuote();

        $this->statsCalculationService->denormalizeSummaryOfRescueQuote($quote);
    }

    public function deleteAggregationOfRescueQuote(WithRescueQuoteEntity $event)
    {
        $quote = $event->getQuote();

        QuoteTotal::query()
            ->where('quote_id', $quote->getKey())
            ->delete();
    }

    public function updateAggregationOfWorldwideQuote(WithWorldwideQuoteEntity $event)
    {
        $quote = $event->getQuote();

        $this->statsCalculationService->denormalizeSummaryOfWorldwideQuote($quote);
    }

    public function deleteAggregationOfWorldwideQuote(WithWorldwideQuoteEntity $event)
    {
        $quote = $event->getQuote();

        QuoteTotal::query()
            ->where('quote_id', $quote->getKey())
            ->delete();
    }

    public function updateAggregationOfOpportunity(WithOpportunityEntity $event)
    {
        $opportunity = $event->getOpportunity();

        //
    }

    public function deleteAggregationOfOpportunity(WithOpportunityEntity $event)
    {
        $opportunity = $event->getOpportunity();

        //
    }
}
