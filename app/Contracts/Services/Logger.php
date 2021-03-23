<?php

namespace App\Contracts\Services;

use Throwable;

interface Logger
{
    /**
     * Format and log all given data.
     *
     * @return void
     */
    public function log(): void;

    /**
     * Format an error with exception.
     *
     * @param string $message
     * @param Throwable $e
     * @return string
     */
    public function formatError(string $message, Throwable $e): string;
}
