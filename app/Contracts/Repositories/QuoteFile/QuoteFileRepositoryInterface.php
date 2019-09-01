<?php namespace App\Contracts\Repositories\QuoteFile;

use Illuminate\Http\Request;
use App\Http\Requests\StoreQuoteFileRequest;
use App\Models\QuoteFile\QuoteFile;

interface QuoteFileRepositoryInterface
{
    /**
     * Get all quote files stored by user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();

    /**
     * Make a new Quote File
     * @params array
     * @return \App\Models\QuoteFile\QuoteFile
     */
    public function make(Array $array);

    /**
     * Create a new Quote File
     * Associate with File Format
     * @param \App\Requests\StoreQuoteFileRequest $request
     * @return \App\Models\QuoteFile\QuoteFile
     */
    public function create(StoreQuoteFileRequest $request);

    /**
     * Store Imported Raw Data by QuoteFile
     *
     * @param QuoteFile $quoteFile
     * @param Array $array
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function createRawData(QuoteFile $quoteFile, Array $array);
}