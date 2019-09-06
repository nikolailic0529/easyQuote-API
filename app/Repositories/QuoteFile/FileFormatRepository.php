<?php namespace App\Repositories\QuoteFile;

use App\Models\QuoteFile\QuoteFileFormat;
use App\Contracts\Repositories\QuoteFile\FileFormatRepositoryInterface;

class FileFormatRepository implements FileFormatRepositoryInterface
{
    protected $quoteFileFormat;

    public function __construct(QuoteFileFormat $quoteFileFormat)
    {
        $this->quoteFileFormat = $quoteFileFormat;
    }

    public function all()
    {
        return $this->quoteFileFormat->all();
    }
}
