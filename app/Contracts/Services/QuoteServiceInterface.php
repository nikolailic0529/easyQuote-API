<?php

namespace App\Contracts\Services;

use App\Models\Quote\{
    BaseQuote as Quote,
    Margin\CountryMargin
};
use Closure;

interface QuoteServiceInterface
{

    /**
     * Route interaction Quote model with other Model
     *
     * @param \App\Models\Quote\Quote $quote
     * @param mixed $model
     * @return \App\Models\Quote\Quote
     */
    public function interact(Quote $quote, $model): void;

    /**
     * Interact with all posible Quote Models
     *
     * @param Quote $quote
     * @return void
     */
    public function interactWithModels(Quote $quote): void;

    /**
     * Interact Quote model with Country Margin
     *
     * @param \App\Models\Quote\Quote $quote
     * @param \App\Models\Quote\Margin\CountryMargin $countryMargin
     * @return \App\Models\Quote\Quote
     */
    public function interactWithCountryMargin(Quote $quote, CountryMargin $countryMargin): void;

    /**
     * Interact with User's Margin and Possible Country Margin
     *
     * @param Quote $quote
     * @return Quote
     */
    public function interactWithMargin(Quote $quote): void;

    /**
     * Calculate Schedule Prices based on Margin Percentage
     *
     * @param Quote $quote
     * @return Quote
     */
    public function calculateSchedulePrices(Quote $quote): void;

    /**
     * Interact Quote model with Discount
     *
     * @param Quote $quote
     * @param mixed $discount
     * @return \App\Models\Quote\Quote
     */
    public function interactWithDiscount(Quote $quote, $discount): void;

    /**
     * Performing all necessary operations with Quote instance.
     * Retrieving Selected Rows Data, Interactions with Margins, Discounts, Calculation Total List Price.
     *
     * @param Quote $quote
     * @return void
     */
    public function prepareQuoteReview(Quote $quote): void;

    /**
     * Format Computable Rows.
     *
     * @param Quote $quote
     * @return void
     */
    public function prepareRows(Quote $quote): void;

    /**
     * Format Payment Schedule.
     *
     * @param Quote $quote
     * @return void
     */
    public function prepareSchedule(Quote $quote): void;

    /**
     * Export Quote in PDF format.
     *
     * @param Quote $quote
     * @return array
     */
    public function export(Quote $quote);
}
