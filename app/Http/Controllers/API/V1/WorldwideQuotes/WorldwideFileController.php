<?php

namespace App\Http\Controllers\API\V1\WorldwideQuotes;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorldwideQuote\StoreDistributorFile;
use App\Http\Requests\WorldwideQuote\StoreScheduleFile;
use App\Models\Quote\WorldwideDistribution;
use App\Models\QuoteFile\QuoteFile;

class WorldwideFileController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \app\Http\Requests\WorldwideQuote\StoreDistributorFile $request
     * @return \Illuminate\Http\Response
     */
    public function storeDistributorFile(StoreDistributorFile $request)
    {
        // 
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \app\Http\Requests\WorldwideQuote\StoreScheduleFile $request
     * @return \Illuminate\Http\Response
     */
    public function storeScheduleFile(StoreScheduleFile $request)
    {
        // 
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Quote\WorldwideDistribution  $worldwideDistribution
     * @param  \App\Models\QuoteFile\QuoteFile  $quoteFile
     * @return \Illuminate\Http\Response
     */
    public function destroy(WorldwideDistribution $worldwideDistribution, QuoteFile $quoteFile)
    {
        //
    }
}
