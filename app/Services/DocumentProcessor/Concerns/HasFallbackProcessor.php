<?php

namespace App\Services\DocumentProcessor\Concerns;

use App\Contracts\Services\ProcessesQuoteFile;

interface HasFallbackProcessor
{
    public function getFallbackProcessor(): ProcessesQuoteFile;
}
