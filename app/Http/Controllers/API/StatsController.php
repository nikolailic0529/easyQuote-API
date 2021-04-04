<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\{Stats\ShowQuoteLocations, Stats\StatsAggregatorRequest as StatsRequest, Stats\Summary,};
use App\Models\Location;
use App\Services\Stats\StatsAggregationService;
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
     *
     * @param Summary $request
     * @return JsonResponse
     */
    public function showSummaryOfQuotes(Summary $request): JsonResponse
    {
        return response()->json(
            $this->stats->getQuotesSummary(
                $request->getSummaryRequestData()
            )
        );
    }

    /**
     * Display a customers summary.
     *
     * @param Summary $request
     * @return JsonResponse
     */
    public function showSummaryOfCustomers(Summary $request): JsonResponse
    {
        return response()->json(
            $this->stats->getCustomersSummaryList(
                $request->getSummaryRequestData()
            )
        );
    }

    /**
     * Display customer totals for map.
     *
     * @param ShowQuoteLocations $request
     * @return JsonResponse
     */
    public function showCustomerLocations(ShowQuoteLocations $request): JsonResponse
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
     *
     * @param ShowQuoteLocations $request
     * @return JsonResponse
     */
    public function showAssetLocations(ShowQuoteLocations $request): JsonResponse
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
     *
     * @param ShowQuoteLocations $request
     * @return JsonResponse
     */
    public function showQuoteLocations(ShowQuoteLocations $request): JsonResponse
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
     *
     * @param Location $location
     * @param StatsRequest $request
     * @return JsonResponse
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
