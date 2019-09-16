<?php namespace App\Contracts\Repositories\Quote;

use App\Http\Requests \ {
    StoreQuoteStateRequest,
    GetQuoteTemplatesRequest,
    FindQuoteTemplateRequest
};

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

    /**
     * Get Quote Templates by Company, Vendor, Country
     *
     * @param GetQuoteTemplatesRequest $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTemplates(GetQuoteTemplatesRequest $request);

    /**
     * Find Quote Template
     * Attach necessary information for the 2 step
     * @param FindQuoteTemplateRequest $request
     * @return \Illuminate\Support\Collection
     */
    public function step2(FindQuoteTemplateRequest $request);
}
