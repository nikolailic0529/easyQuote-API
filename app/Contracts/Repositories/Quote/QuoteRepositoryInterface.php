<?php namespace App\Contracts\Repositories\Quote;

use App\Http\Requests\StoreQuoteStateRequest;

interface QuoteRepositoryInterface
{
    /**
     * Store current state of the Quote
     *
     * @param StoreQuoteStateRequest $request
     * @return \App\Models\Quote\Quote
     */
    public function storeState(StoreQuoteStateRequest $request);

    /**
     * Return linked data Company->Vendor->Country->Language and Data Select Separators
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function step1();
}