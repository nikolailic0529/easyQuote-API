<?php namespace App\Repositories\QuoteFile;

use App\Models\QuoteFile\QuoteFileFormat;
use App\Contracts\Repositories\QuoteFile\FileFormatRepositoryInterface;

class FileFormatRepository implements FileFormatRepositoryInterface
{
    public function all()
    {
        return QuoteFileFormat::all();
    }
}
