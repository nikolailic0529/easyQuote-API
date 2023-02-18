<?php

namespace App\Domain\Worldwide\Events\Quote;

use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WorldwideQuoteFilesExported
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(protected WorldwideQuote $quote,
                                protected Collection $exportedFiles)
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
