<?php

namespace App\Events\RescueQuote;

use App\Models\QuoteFile\QuoteFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RescueQuoteFileExported
{
    use Dispatchable, SerializesModels;

    public function __construct(protected QuoteFile $quoteFile,
                                protected Model     $parentEntity)
    {
    }

    public function getParentEntity(): Model
    {
        return $this->parentEntity;
    }

    /**
     * @return QuoteFile
     */
    public function getQuoteFile(): QuoteFile
    {
        return $this->quoteFile;
    }
}
