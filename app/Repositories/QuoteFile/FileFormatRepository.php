<?php

namespace App\Repositories\QuoteFile;

use App\Contracts\Repositories\QuoteFile\FileFormatRepositoryInterface;
use App\Models\QuoteFile\QuoteFileFormat;

class FileFormatRepository implements FileFormatRepositoryInterface
{
    protected QuoteFileFormat $quoteFileFormat;

    public function __construct(QuoteFileFormat $quoteFileFormat)
    {
        $this->quoteFileFormat = $quoteFileFormat;
    }

    public function all()
    {
        return $this->quoteFileFormat->all();
    }

    public function whereInExtension(array $array)
    {
        return $this->quoteFileFormat->whereIn('extension', $array)->firstOrFail();
    }
}
