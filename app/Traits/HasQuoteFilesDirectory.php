<?php

namespace App\Traits;

trait HasQuoteFilesDirectory
{
    public function getQuoteFilesDirectoryAttribute()
    {
        return "quotes/{$this->{$this->getKeyName()}}";
    }
}