<?php

namespace App\Domain\Rescue\Events\Quote;

use App\Domain\QuoteFile\Models\QuoteFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RescueQuoteFileExported
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(protected QuoteFile $quoteFile,
                                protected Model $parentEntity)
    {
    }

    public function getParentEntity(): Model
    {
        return $this->parentEntity;
    }

    public function getQuoteFile(): QuoteFile
    {
        return $this->quoteFile;
    }
}
