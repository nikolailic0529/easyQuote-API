<?php namespace App\Contracts\Services;

use App\Models\Quote \ {
    Quote,
    Discount,
    Margin\CountryMargin
};
use Illuminate\Http\Response;
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
    public function interact(Quote $quote, $model): Quote;

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
    public function interactWithCountryMargin(Quote $quote, CountryMargin $countryMargin): Quote;

    /**
     * Interact with User's Margin and Possible Country Margin
     *
     * @param Quote $quote
     * @return Quote
     */
    public function interactWithMargin(Quote $quote): Quote;

    /**
     * Calculate Schedule Prices based on Margin Percentage
     *
     * @param Quote $quote
     * @return Quote
     */
    public function calculateSchedulePrices(Quote $quote): Quote;

    /**
     * Interact Quote model with Discount
     *
     * @param Quote $quote
     * @param mixed $discount
     * @return \App\Models\Quote\Quote
     */
    public function interactWithDiscount(Quote $quote, $discount): Quote;

    /**
     * Modify Column in Quote Computable Rows
     *
     * @param Quote $quote
     * @param string $column
     * @param Closure $callback
     * @return void
     */
    public function modifyColumn(Quote $quote, string $column, Closure $callback): void;

    /**
     * Performing all necessary operations with Quote instance.
     * Retrieving Selected Rows Data, Interactions with Margins, Discounts, Calculation Total List Price.
     *
     * @param Quote $quote
     * @return void
     */
    public function prepareQuoteReview(Quote $quote): void;

    /**
     * Prepare Quote Data for PDF rendering.
     *
     * @param Quote $quote
     * @return array
     */
    public function prepareQuoteExport(Quote $quote): array;

    /**
     * Inline output Generated PDF Quote file.
     *
     * @param Quote $quote
     * @return mixed
     */
    public function inlinePdf(Quote $quote);

    /**
     * Export Quote in PDF format.
     *
     * @param Quote $quote
     * @return array
     */
    public function export(Quote $quote);
}
