<?php

namespace App\Contracts\Services;

use Symfony\Component\Console\Output\OutputInterface;

interface Stats
{
    public function setOutput(OutputInterface $output): self;

    /**
     * Calculate and denormalize quote totals.
     *
     * @return void
     */
    public function denormalizeSummaryOfQuotes(): void;

    /**
     * Calculate and denormalize quote totals per each location.
     *
     * @return void
     */
    public function denormalizeSummaryOfLocations(): void;

    /**
     * Calculate and denormalize customer totals base on quote totals.
     *
     * @return void
     */
    public function denormalizeSummaryOfCustomers(): void;

    /**
     * Calculate and denormalize asset totals base on existing assets.
     *
     * @return void
     */
    public function denormalizeSummaryOfAssets(): void;
}
