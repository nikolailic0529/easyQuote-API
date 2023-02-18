<?php

namespace App\Domain\Stats\Listeners;

use App\Domain\Asset\Contracts\WithAssetEntity;
use App\Domain\Asset\Events\AssetCreated;
use App\Domain\Asset\Events\AssetDeleted;
use App\Domain\Asset\Events\AssetUpdated;
use App\Domain\Rescue\Contracts\WithRescueQuoteEntity;
use App\Domain\Rescue\Events\Quote\RescueQuoteCreated;
use App\Domain\Rescue\Events\Quote\RescueQuoteDeleted;
use App\Domain\Rescue\Events\Quote\RescueQuoteSubmitted;
use App\Domain\Rescue\Events\Quote\RescueQuoteUnravelled;
use App\Domain\Rescue\Events\Quote\RescueQuoteUpdated;
use App\Domain\Stats\Models\QuoteTotal;
use App\Domain\Stats\Services\StatsCalculationService;
use App\Domain\Worldwide\Contracts\WithOpportunityEntity;
use App\Domain\Worldwide\Contracts\WithWorldwideQuoteEntity;
use App\Domain\Worldwide\Events\Opportunity\OpportunityCreated;
use App\Domain\Worldwide\Events\Opportunity\OpportunityDeleted;
use App\Domain\Worldwide\Events\Opportunity\OpportunityMarkedAsLost;
use App\Domain\Worldwide\Events\Opportunity\OpportunityMarkedAsNotLost;
use App\Domain\Worldwide\Events\Opportunity\OpportunityUpdated;
use App\Domain\Worldwide\Events\Quote\WorldwideContractQuoteDiscountStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwideContractQuoteImportStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwideContractQuoteMappingReviewStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwideContractQuoteMappingStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwideContractQuoteMarginStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwidePackQuoteAssetsCreationStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwidePackQuoteAssetsReviewStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwidePackQuoteDiscountStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwidePackQuoteMarginStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteDeleted;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteInitialized;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteMarkedAsAlive;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteMarkedAsDead;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteSubmitted;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteUnraveled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher as EventDispatcher;

class StatsDependentEntityEventSubscriber implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(protected StatsCalculationService $statsCalculationService)
    {
    }

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
    }

    public function deleteAggregationOfOpportunity(WithOpportunityEntity $event)
    {
        $opportunity = $event->getOpportunity();
    }
}
