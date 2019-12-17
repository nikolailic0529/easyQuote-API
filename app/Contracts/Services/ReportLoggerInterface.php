<?php

namespace App\Contracts\Services;

interface ReportLoggerInterface
{
    /**
     * Format and log all given data.
     *
     * @return void
     */
    public function log(): void;
}
