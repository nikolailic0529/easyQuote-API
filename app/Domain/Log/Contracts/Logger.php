<?php

namespace App\Domain\Log\Contracts;

interface Logger
{
    /**
     * Format and log all given data.
     */
    public function log(): void;

    /**
     * Format an error with exception.
     */
    public function formatError(string $message, \Throwable $e): string;
}
