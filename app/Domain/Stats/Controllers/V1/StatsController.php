<?php

namespace App\Domain\Stats\Controllers\V1;

use App\Domain\Location\Models\Location;
use App\Domain\Stats\Requests\ShowQuoteLocationsRequest;
use App\Domain\Stats\Requests\StatsAggregatorRequest as StatsRequest;
use App\Domain\Stats\Requests\SummaryRequest;
use App\Domain\Stats\Services\StatsAggregationService;
use App\Foundation\Http\Controller;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    protected StatsAggregationService $stats;

    public function __construct(StatsAggregationService $stats)
    {
        $this->stats = $stats;
    }

    /**
     * Display a quotes summary.
     */
    public function showSummaryOfQuotes(SummaryRequest $request): JsonResponse
    {
        return response()->json(
            $this->stats->getQuotesSummary(
                $request->getSummaryRequestData()
            )
        );
    }

    /**
     * Display a customers summary.
     */
    public function showSummaryOfCustomers(SummaryRequest $request): JsonResponse
    {
        return response()->json(
            $this->stats->getCustomersSummaryList(
                $request->getSummaryRequestData()
            )
        );
    }

    /**
     * Display customer totals for map.
     */
    public function showCustomerLocations(ShowQuoteLocationsRequest $request): JsonResponse
    {
        return response()->json(
            $this->stats->getCustomerTotalLocations(
                $request->getPolygon(),
                $request->resolveEntityKeyOfActingUser()
            )
        );
    }

    /**
     * Display asset totals for map.
     */
    public function showAssetLocations(ShowQuoteLocationsRequest $request): JsonResponse
    {
        return response()->json(
            $this->stats->getAssetTotalLocations(
                $request->getPointOfCenter(),
                $request->getPolygon(),
                $request->resolveEntityKeyOfActingUser()
            )
        );
    }

    /**
     * Display quote totals for map.
     */
    public function showQuoteLocations(ShowQuoteLocationsRequest $request): JsonResponse
    {
        return response()->json(
            $this->stats->getQuoteLocations(
                $request->getPointOfCenter(),
                $request->getPolygon(),
                $request->resolveEntityKeyOfActingUser()
            )
        );
    }

    /**
     * Display quote totals by specific location.
     */
    public function showQuotesOfLocation(StatsRequest $request, Location $location): JsonResponse
    {
        return response()->json(
            $this->stats->getQuoteTotalLocations(
                $location->getKey(),
                $request->resolveEntityKeyOfActingUser()
            )
        );
    }
}
