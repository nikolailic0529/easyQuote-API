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
     * @param Int $page
     * @return \Illuminate\Support\Collection
     */
    public function createColumnData(QuoteFile $quoteFile, Array $array, $page);

    /**
     * Get Imported Raw Data on second page by default from QuoteFile
     *
     * @param QuoteFile $quoteFile
     * @param Int $page
     * @return Array $array
     */
    public function getRawData(QuoteFile $quoteFile, Int $page = 2);

    /**
     * Determine File format before storing
     * Will throw exception, if File Format doesn't exist
     *
     * @param UploadedFile $file
     * @return \App\Models\QuoteFile\QuoteFileFormat
     */
    public function determineFileFormat(UploadedFile $file);
}
