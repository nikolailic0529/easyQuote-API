<?php

namespace App\Domain\Stats\Contracts;

use Symfony\Component\Console\Output\OutputInterface;

interface Stats
{
    public function setOutput(OutputInterface $output): self;

    /**
     * Calculate and denormalize quote totals.
     */
    public function denormalizeSummaryOfQuotes(): void;

    /**
     * Calculate and denormalize quote totals per each location.
     */
    public function denormalizeSummaryOfLocations(): void;

    /**
     * Calculate and denormalize customer totals base on quote totals.
     */
    public function denormalizeSummaryOfCustomers(): void;

    /**
     * Calculate and denormalize asset totals base on existing assets.
     */
    public function denormalizeSummaryOfAssets(): void;
}
