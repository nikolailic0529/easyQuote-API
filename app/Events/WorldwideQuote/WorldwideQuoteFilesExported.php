<?php

namespace App\Events\WorldwideQuote;

use App\Models\Quote\WorldwideQuote;
use App\Models\QuoteFile\QuoteFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WorldwideQuoteFilesExported
{
    use Dispatchable, SerializesModels;


    public function __construct(protected WorldwideQuote $quote,
                                protected Collection     $exportedFiles)
    {
    }

    public function getQuote(): WorldwideQuote
    {
        return $this->quote;
    }

    /**
     * @return Collection<int, QuoteFile>
     */
    public function getExportedFiles(): Collection
    {
        return $this->exportedFiles;
    }
}
