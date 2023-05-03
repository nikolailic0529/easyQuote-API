<?php

namespace App\Domain\Worldwide\Controllers\V1\Quote;

use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Requests\Quote\StoreDistributorFileRequest;
use App\Domain\Worldwide\Requests\Quote\StoreScheduleFileRequest;
use App\Foundation\Http\Controller;

class WorldwideFileController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function storeDistributorFile(StoreDistributorFileRequest $request)
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function storeScheduleFile(StoreScheduleFileRequest $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(WorldwideDistribution $worldwideDistribution, QuoteFile $quoteFile)
    {
    }
}
