<?php namespace App\Contracts\Repositories\QuoteFile;

use Illuminate\Http\Request;
use App\Http\Requests\StoreQuoteFileRequest;
use App\Models\QuoteFile\QuoteFile;
use Illuminate\Http\UploadedFile;

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

    /**
     * Store Parsed Column Data by QuoteFile
     * And mark QuoteFile as handled
     * @param QuoteFile $quoteFile
     * @param Array $array
     * @param void $requestedPage
     * @return \Illuminate\Support\Collection
     */
    public function createRowsData(QuoteFile $quoteFile, Array $array, $requestedPage);

    /**
     * Get Imported Raw Data on second page by default from QuoteFile
     *
     * @param QuoteFile $quoteFile
     * @param Int $page
     * @return array
     */
    public function getRawData(QuoteFile $quoteFile);

    /**
     * Get all parsed column data from Quote File
     *
     * @param QuoteFile $quoteFile
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRowsData(QuoteFile $quoteFile, Int $page);

    /**
     * Get Quote File
     *
     * @param String $id
     * @return QuoteFile
     */
    public function get(String $id);

    /**
     * Check for existing
     *
     * @param String $id
     * @return void
     */
    public function exists(String $id);
}
