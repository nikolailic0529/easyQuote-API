<?php namespace App\Contracts\Services;

use App\Models\Quote \ {
    Quote,
    Discount,
    Margin\CountryMargin
};
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

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
}
