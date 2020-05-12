<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\{
    Stats\Map,
    Stats\Summary,
};
use App\Models\Location;
use App\Services\StatsAggregator;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    protected StatsAggregator $stats;

    public function __construct(StatsAggregator $stats)
    {
        $this->stats = $stats;
    }

    public function quotesSummary(Summary $request)
    {
        return response()->json(
            $this->stats->quotesSummary($request->period(), $request->country_id)
        );
    }

    public function customersSummary(Summary $request)
    {
        return response()->json(
            $this->stats->customersSummaryList($request->period(), $request->country_id)
        );
    }

    public function mapCustomers(Map $request)
    {
        return response()->json(
            $this->stats->mapCustomerTotals(
                $request->polygon()
            )
        );
    }

    public function mapAssets(Map $request)
    {
        return response()->json(
            $this->stats->mapAssetTotals(
                $request->centerPoint(),
                $request->polygon()
            )
        );
    }

    public function mapQuotes(Map $request)
    {
        return response()->json(
            $this->stats->mapQuoteTotals(
                $request->centerPoint(),
                $request->polygon()
            )
        );
    }

    public function quotesByLocation(Location $location)
    {
        return response()->json(
            $this->stats->quotesByLocation($location->id)
        );
    }
}
