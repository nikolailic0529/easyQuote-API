<?php

namespace App\Contracts\Services;

use App\Models\Quote\{
    BaseQuote,
    Margin\CountryMargin,
    Quote
};
use Closure;
use Illuminate\Support\Collection;

interface QuoteServiceInterface
{

    /**
     * Route interaction BaseQuote model with other Model
     *
     * @param BaseQuote $quote
     * @param mixed $model
     * @return BaseQuote
     */
    public function interact(BaseQuote $quote, $model): void;

    /**
     * Interact with all posible BaseQuote Models
     *
     * @param BaseQuote $quote
     * @return void
     */
    public function interactWithModels(BaseQuote $quote): void;

    /**
     * Interact BaseQuote model with Country Margin
     *
     * @param BaseQuote $quote
     * @param CountryMargin $countryMargin
     * @return BaseQuote
     */
    public function interactWithCountryMargin(BaseQuote $quote, CountryMargin $countryMargin): void;

    /**
     * Interact with User's Margin and Possible Country Margin
     *
     * @param BaseQuote $quote
     * @return BaseQuote
     */
    public function interactWithMargin(BaseQuote $quote): void;

    /**
     * Calculate Schedule Prices based on Margin Percentage
     *
     * @param BaseQuote $quote
     * @return BaseQuote
     */
    public function calculateSchedulePrices(BaseQuote $quote): void;

    /**
     * Interact BaseQuote model with Discount
     *
     * @param BaseQuote $quote
     * @param mixed $discount
     * @return BaseQuote
     */
    public function interactWithDiscount(BaseQuote $quote, $discount): void;

    /**
     * Performing all necessary operations with BaseQuote instance.
     * Retrieving Selected Rows Data, Interactions with Margins, Discounts, Calculation Total List Price.
     *
     * @param BaseQuote $quote
     * @return void
     */
    public function prepareQuoteReview(BaseQuote $quote): void;

    /**
     * Format Computable Rows.
     *
     * @param BaseQuote $quote
     * @return void
     */
    public function prepareRows(BaseQuote $quote): void;

    /**
     * Format Payment Schedule.
     *
     * @param BaseQuote $quote
     * @return void
     */
    public function prepareSchedule(BaseQuote $quote): void;

    /**
     * Export BaseQuote in PDF format.
     *
     * @param BaseQuote $quote
     * @return array
     */
    public function export(BaseQuote $quote);

    /**
     * Handle quote granted users.
     *
     * @param Quote $quote
     * @param array $users
     * @return mixed
     */
    public function handleQuoteGrantedUsers(Quote $quote, array $users);
}
