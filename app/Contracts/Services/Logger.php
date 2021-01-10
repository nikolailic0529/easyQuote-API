<?php

namespace App\Contracts\Services;

use Throwable;

interface Logger
{
    /**
     * Format and log all given data.
     *
     * @param array $message
     * @param array $context
     * @return void
     */
    public function log($message, $context = []): void;

    /**
     * Format an error with exception.
     *
     * @param string $message
     * @param Throwable $e
     * @return string
     */
    public function formatError(string $message, Throwable $e): string;
}
