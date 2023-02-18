<?php

namespace App\Domain\DocumentProcessing\Concerns;

use App\Domain\DocumentProcessing\Contracts\ProcessesQuoteFile;

interface HasFallbackProcessor
{
    public function getFallbackProcessor(): ProcessesQuoteFile;
}
