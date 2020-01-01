<?php

namespace App\Traits;

trait HasQuoteFilesDirectory
{
    public function getQuoteFilesDirectoryAttribute(): string
    {
        return "quotes/{$this->{$this->getKeyName()}}";
    }
}
