<?php namespace App\Contracts\Repositories\QuoteFile;

use Illuminate\Http \ {
    Request,
    UploadedFile
};
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
    public function make(array $array);

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
     * @param array $array
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function createRawData(QuoteFile $quoteFile, array $array);

    /**
     * Store Parsed Column Data by QuoteFile
     * And mark QuoteFile as handled
     * @param QuoteFile $quoteFile
     * @param array $array
     * @param void $requestedPage
     * @return \Illuminate\Support\Collection
     */
    public function createRowsData(QuoteFile $quoteFile, array $array, $requestedPage);

    /**
     * Store Parsed Payment Schedule Data by QuoteFile
     *
     * @param QuoteFile $quoteFile
     * @param array $array
     * @return \App\Models\QuoteFile\ScheduleData
     */
    public function createScheduleData(QuoteFile $quoteFile, array $value);

    /**
     * Get Imported Raw Data from QuoteFile
     *
     * @param QuoteFile $quoteFile
     * @param int $page
     * @return array
     */
    public function getRawData(QuoteFile $quoteFile);

    /**
     * Get all parsed column data from Quote File
     *
     * @param QuoteFile $quoteFile
     * @param int $requestedPage
     * @return [\Illuminate\Database\Eloquent\Collection]
     */
    public function getRowsData(QuoteFile $quoteFile, int $requestedPage);

    /**
     * Get Imported Schedule Data from Payment Schedule type Quote File
     *
     * @param QuoteFile $quoteFile
     * @return \App\Models\QuoteFile\ScheduleData
     */
    public function getScheduleData(QuoteFile $quoteFile);

    /**
     * Get Quote File
     *
     * @param String $id
     * @return QuoteFile
     */
    public function find(String $id);

    /**
     * Check for existing
     *
     * @param String $id
     * @return void
     */
    public function exists(String $id);
}
