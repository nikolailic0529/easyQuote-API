<?php

namespace App\Services\HandledCases;

use App\Models\QuoteFile\QuoteFile;
use Closure;

abstract class HandledCase
{
    public function handle(QuoteFile $quoteFile, Closure $next)
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
