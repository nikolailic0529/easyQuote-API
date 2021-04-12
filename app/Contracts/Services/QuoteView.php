<?php

namespace App\Contracts\Services;

use App\Models\Quote\{
    BaseQuote,
    Margin\CountryMargin,
};
use Illuminate\Http\Response;

interface QuoteView
{
    /**
     * Prepare a quote for an external request.
     *
     * @param string $RFQNumber
     * @param string $clientName
     * @return BaseQuote
     */
    public function requestForQuote(string $RFQNumber, string $clientName = null): BaseQuote;

    /**
     * Route interaction BaseQuote model with other Model
     *
     * @param BaseQuote $quote
     * @param mixed $model
     * @return $this
     */
    public function interact(BaseQuote $quote, $model);

    /**
     * Interact with all posible BaseQuote Models
     *
     * @param BaseQuote $quote
     * @return $this
     */
    public function interactWithModels(BaseQuote $quote);

    /**
     * Interact BaseQuote model with Country Margin
     *
     * @param BaseQuote $quote
     * @param CountryMargin $countryMargin
     * @return $this
     */
    public function interactWithCountryMargin(BaseQuote $quote, CountryMargin $countryMargin);

    /**
     * Interact with User's Margin and Possible Country Margin
     *
     * @param BaseQuote $quote
     * @return $this
     */
    public function interactWithMargin(BaseQuote $quote);

    /**
     * Set computable rows to given quote instance.
     *
     * @param BaseQuote $quote
     * @return $this
     */
    public function setComputableRows(BaseQuote $quote);

    /**
     * Calculate Schedule Prices based on Margin Percentage
     *
     * @param BaseQuote $quote
     * @return $this
     */
    public function calculateSchedulePrices(BaseQuote $quote);

    /**
     * Interact BaseQuote model with Discount
     *
     * @param BaseQuote $quote
     * @param mixed $discount
     * @return $this
     */
    public function interactWithDiscount(BaseQuote $quote, $discount);

    /**
     * Performing all necessary operations with BaseQuote instance.
     * Retrieving Selected Rows Data, Interactions with Margins, Discounts, Calculation Total List Price.
     *
     * @param BaseQuote $quote
     * @return $this
     */
    public function prepareQuoteReview(BaseQuote $quote);

    /**
     * Format Computable Rows.
     *
     * @param BaseQuote $quote
     * @return $this
     */
    public function prepareRows(BaseQuote $quote);

    /**
     * Format Payment Schedule.
     *
     * @param BaseQuote $quote
     * @return $this
     */
    public function prepareSchedule(BaseQuote $quote);

    /**
     * Export BaseQuote in PDF format.
     *
     * @param BaseQuote $quote
     * @param int $type
     * @return Response
     */
    public function export(BaseQuote $quote, int $type): Response;
}
