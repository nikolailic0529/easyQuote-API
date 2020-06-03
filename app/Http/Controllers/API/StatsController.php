<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\{
    Stats\Map,
    Stats\Summary,
    Stats\Request as StatsRequest,
};
use App\Models\Location;
use App\Services\StatsAggregator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StatsController extends Controller
{
    protected StatsAggregator $stats;

    public function __construct(StatsAggregator $stats)
    {
        $this->stats = $stats;
    }

    /**
     * Display a quotes summary.
     *
     * @param Summary $request
     * @return \Illuminate\Http\Response
     */
    public function quotesSummary(Summary $request)
    {
        return response()->json(
            $this->stats->quotesSummary(
                $request->period(),
                $request->country_id,
                $request->currency_id,
                $request->userId()
            )
        );
    }

    /**
     * Display a customers summary.
     *
     * @param Summary $request
     * @return \Illuminate\Http\Response
     */
    public function customersSummary(Summary $request)
    {
        return response()->json(
            $this->stats->customersSummaryList(
                $request->period(),
                $request->country_id,
                $request->currency_id,
                $request->userId()
            )
        );
    }

    /**
     * Display customer totals for map.
     *
     * @param Map $request
     * @return \Illuminate\Http\Response
     */
    public function mapCustomers(Map $request)
    {
        return response()->json(
            $this->stats->mapCustomerTotals(
                $request->polygon(),
                $request->userId()
            )
        );
    }

    /**
     * Display asset totals for map.
     *
     * @param Map $request
     * @return \Illuminate\Http\Response
     */
    public function mapAssets(Map $request)
    {
        return response()->json(
            $this->stats->mapAssetTotals(
                $request->centerPoint(),
                $request->polygon(),
                $request->userId()
            )
        );
    }

    /**
     * Display quote totals for map.
     *
     * @param Map $request
     * @return \Illuminate\Http\Response
     */
    public function mapQuotes(Map $request)
    {
        return response()->json(
            $this->stats->mapQuoteTotals(
                $request->centerPoint(),
                $request->polygon(),
                $request->userId()
            )
        );
    }

    /**
     * Display quote totals by specific location.
     *
     * @param Location $location
     * @param StatsRequest $request
     * @return \Illuminate\Http\Response
     */
    public function quotesByLocation(Location $location, StatsRequest $request)
    {
        return response()->json(
            $this->stats->quotesByLocation(
                $location->id,
                $request->userId()
            )
        );
    }
}
