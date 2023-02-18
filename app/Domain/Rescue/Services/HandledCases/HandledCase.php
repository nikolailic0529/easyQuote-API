<?php

namespace App\Domain\Rescue\Services\HandledCases;

use App\Domain\QuoteFile\Models\QuoteFile;

abstract class HandledCase
{
    public function handle(QuoteFile $quoteFile, \Closure $next)
    {
        if ($quoteFile->shouldBeHandled) {
            return $next($quoteFile);
        }

        if (value($this->applyCase($quoteFile)) === true) {
            return $next($quoteFile->shouldHandle());
        }

        return $next($quoteFile);
    }

    abstract protected function applyCase(QuoteFile $quoteFile);
}
