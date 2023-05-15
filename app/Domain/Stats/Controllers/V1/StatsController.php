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
    /**
     * Show quotes summary.
     */
    public function showSummaryOfQuotes(SummaryRequest $request, StatsAggregationService $service): JsonResponse
    {
        return response()->json(
            $service->getQuotesSummary(
                $request->getSummaryRequestData()
            )
        );
    }

    /**
     * Show customers summary.
     */
    public function showSummaryOfCustomers(SummaryRequest $request, StatsAggregationService $service): JsonResponse
    {
        return response()->json(
            $service->getCustomersSummaryList(
                $request->getSummaryRequestData()
            )
        );
    }

    /**
     * Show customer locations.
     */
    public function showCustomerLocations(ShowQuoteLocationsRequest $request, StatsAggregationService $service): JsonResponse
    {
        return response()->json(
            $service->getCustomerTotalLocations(
                $request->getPolygon(),
                $request->resolveEntityKeyOfActingUser()
            )
        );
    }

    /**
     * Show asset locations.
     */
    public function showAssetLocations(ShowQuoteLocationsRequest $request, StatsAggregationService $service): JsonResponse
    {
        return response()->json(
            $service->getAssetTotalLocations(
                $request->getPointOfCenter(),
                $request->getPolygon(),
                $request->resolveEntityKeyOfActingUser()
            )
        );
    }

    /**
     * Show quote locations.
     */
    public function showQuoteLocations(ShowQuoteLocationsRequest $request, StatsAggregationService $service): JsonResponse
    {
        return response()->json(
            $service->getQuoteLocations(
                $request->getPointOfCenter(),
                $request->getPolygon(),
                $request->resolveEntityKeyOfActingUser()
            )
        );
    }

    /**
     * Show quote location details.
     */
    public function showQuotesOfLocation(StatsRequest $request, StatsAggregationService $service, Location $location): JsonResponse
    {
        return response()->json(
            $service->getQuoteTotalLocations(
                $location->getKey(),
                $request->resolveEntityKeyOfActingUser()
            )
        );
    }
}
