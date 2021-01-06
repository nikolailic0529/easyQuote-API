<?php

namespace App\Contracts\Repositories\QuoteFile;

use App\Models\{
    Quote\BaseQuote as Quote,
    QuoteFile\QuoteFile
};
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
     * Store Imported Raw Data by QuoteFile
     *
     * @param QuoteFile $quoteFile
     * @param array $array
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function createRawData(QuoteFile $quoteFile, array $array);

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
     * @return \Illuminate\Support\Collection
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
     * Retrieve Quote file by specific clause.
     *
     * @param array $clause
     * @return QuoteFile|null
     */
    public function findByClause(array $clause);

    /**
     * Check for existing
     *
     * @param string $id
     * @return void
     */
    public function exists(string $id);

    /**
     * Full replicates a provided QuoteFile and its all Imported Data.
     *
     * @param QuoteFile $quoteFile
     * @param string|null $quoteId
     * @return QuoteFile
     */
    public function replicatePriceList(QuoteFile $quoteFile, ?string $quoteId = null): QuoteFile;
}
