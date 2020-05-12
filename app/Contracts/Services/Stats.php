<?php

namespace App\Contracts\Services;

interface Stats
{
    /**
     * Calculate and denormilize quote totals.
     *
     * @return void
     */
    public function calculateQuoteTotals(): void;

    /**
     * Calculate and denormilize quote totals per each location.
     *
     * @return void
     */
    public function calculateQuoteLocationTotals(): void;

    /**
     * Calculate and denormilize customer totals base on quote totals.
     *
     * @return void
     */
    public function calculateCustomerTotals(): void;

    /**
     * Calculate and denormilize asset totals base on existing assets.
     *
     * @return void
     */
    public function calculateAssetTotals(): void;
}