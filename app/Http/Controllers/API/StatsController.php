<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stats\Summary;
use App\Models\Quote\Quote;
use App\Services\StatsAggregator;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function __invoke(Summary $request, StatsAggregator $stats)
    {
        return response()->json(
            $stats->summary($request->period())
        );
    }
}
