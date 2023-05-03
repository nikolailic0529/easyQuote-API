<?php

namespace App\Domain\QuoteFile\Concerns;

trait HasQuoteFilesDirectory
{
    public function getQuoteFilesDirectoryAttribute(): string
    {
        return "quotes/{$this->{$this->getKeyName()}}";
    }
}
