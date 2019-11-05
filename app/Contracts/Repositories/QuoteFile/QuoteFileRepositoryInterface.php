<?php namespace App\Contracts\Repositories\QuoteFile;

use App\Models \ {
    Quote\Quote,
    QuoteFile\QuoteFile
};

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
     * @param array $attributes
     * @return \App\Models\QuoteFile\QuoteFile
     */
    public function create(array $attributes);

    /**
     * Create a new Generated PDF Quote File.
     *
     * @param Quote $quote
     * @param array $attributes
     * @return \App\Models\QuoteFile\QuoteFile|null
     */
    public function createPdf(Quote $quote, array $attributes);

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
     * @return \Illuminate\Support\Collection
     */
    public function createRowsData(QuoteFile $quoteFile, array $array);

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
     *
     * @return [\Illuminate\Database\Eloquent\Collection]
     */
    public function getRowsData(QuoteFile $quoteFile);

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
    public function find(string $id);

    /**
     * Check for existing
     *
     * @param string $id
     * @return void
     */
    public function exists(string $id);

    /**
     * Delete all Quote Files by type from Quote excepting passed
     *
     * @param QuoteFile $quoteFile
     * @return void
     */
    public function deleteExcept(QuoteFile $quoteFile);

    /**
     * Delete all Quote Files with Payment Schedules type from Quote excepting passed
     *
     * @param QuoteFile $quoteFile
     * @return void
     */
    public function deletePaymentSchedulesExcept(QuoteFile $quoteFile);

    /**
     * Delete all Quote Files with type Distributor Price List from Quote excepting passed
     *
     * @param QuoteFile $quoteFile
     * @return void
     */
    public function deletePriceListsExcept(QuoteFile $quoteFile);
}
