<?php

namespace App\Domain\Rescue\Contracts;

use App\Domain\Authentication\Contracts\CauserAware;
use App\Domain\Margin\Models\CountryMargin;
use App\Domain\Rescue\Models\BaseQuote;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

interface QuoteView extends CauserAware
{
    /**
     * Prepare a quote for an external request.
     */
    public function requestForQuote(string $rfqNumber): BaseQuote;

    /**
     * Route interaction BaseQuote model with other Model.
     *
     * @param mixed $model
     *
     * @return $this
     */
    public function interact(BaseQuote $quote, $model);

    /**
     * Interact with all posible BaseQuote Models.
     *
     * @return $this
     */
    public function interactWithModels(BaseQuote $quote);

    /**
     * Interact BaseQuote model with Country Margin.
     *
     * @return $this
     */
    public function interactWithCountryMargin(BaseQuote $quote, CountryMargin $countryMargin);

    /**
     * Interact with User's Margin and Possible Country Margin.
     *
     * @return $this
     */
    public function interactWithMargin(BaseQuote $quote);

    /**
     * Set computable rows to given quote instance.
     *
     * @return $this
     */
    public function setComputableRows(BaseQuote $quote);

    /**
     * Interact BaseQuote model with Discount.
     *
     * @param mixed $discount
     *
     * @return $this
     */
    public function interactWithDiscount(BaseQuote $quote, $discount);

    /**
     * Performing all necessary operations with BaseQuote instance.
     * Retrieving Selected Rows Data, Interactions with Margins, Discounts, Calculation Total List Price.
     *
     * @return $this
     */
    public function prepareQuoteReview(BaseQuote $quote);

    /**
     * Format Computable Rows.
     *
     * @return $this
     */
    public function prepareRows(BaseQuote $quote);

    /**
     * Format Payment Schedule.
     *
     * @return $this
     */
    public function prepareSchedule(BaseQuote $quote);

    /**
     * Export BaseQuote in PDF format.
     */
    public function export(BaseQuote $quote, int $type = QT_TYPE_QUOTE): Response;

    /**
     * Build view data of quote in html format.
     */
    public function buildView(BaseQuote $quote, int $type): View;
}
