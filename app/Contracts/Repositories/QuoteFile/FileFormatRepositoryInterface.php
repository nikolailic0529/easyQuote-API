<?php namespace App\Contracts\Repositories\QuoteFile;

use App\Http\Requests\StoreQuoteFileRequest;

interface FileFormatRepositoryInterface
{
    /**
     * Get all available Quote File Formats
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();
}
