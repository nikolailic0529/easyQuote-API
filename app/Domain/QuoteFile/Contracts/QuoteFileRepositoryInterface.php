<?php

namespace App\Domain\QuoteFile\Contracts;

use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\Rescue\Models\BaseQuote as Quote;

interface QuoteFileRepositoryInterface
{
    /**
     * Get all quote files stored by user.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();

    /**
     * Make a new Quote File.
     *
     * @params array
     *
     * @return \App\Domain\QuoteFile\Models\QuoteFile
     */
    public function make(array $array);

    /**
     * Create a new Quote File
     * Associate with File Format.
     *
     * @return \App\Domain\QuoteFile\Models\QuoteFile
     */
    public function create(array $attributes);

    /**
     * Store Imported Raw Data by QuoteFile.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function createRawData(QuoteFile $quoteFile, array $array);

    /**
     * Store Parsed Payment Schedule Data by QuoteFile.
     *
     * @param array $array
     *
     * @return \App\Domain\QuoteFile\Models\ScheduleData
     */
    public function createScheduleData(QuoteFile $quoteFile, array $value);

    /**
     * Get Imported Raw Data from QuoteFile.
     *
     * @param int $page
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRawData(QuoteFile $quoteFile);

    /**
     * Get all parsed column data from Quote File.
     *
     * @return [\Illuminate\Database\Eloquent\Collection]
     */
    public function getRowsData(QuoteFile $quoteFile);

    /**
     * Get Imported Schedule Data from Payment Schedule type Quote File.
     *
     * @return \App\Domain\QuoteFile\Models\ScheduleData
     */
    public function getScheduleData(QuoteFile $quoteFile);

    /**
     * Get Quote File.
     *
     * @return QuoteFile
     */
    public function find(string $id);

    /**
     * Retrieve Quote file by specific clause.
     *
     * @return QuoteFile|null
     */
    public function findByClause(array $clause);

    /**
     * Check for existing.
     *
     * @return void
     */
    public function exists(string $id);

    /**
     * Full replicates a provided QuoteFile and its all Imported Data.
     */
    public function replicatePriceList(QuoteFile $quoteFile, ?string $quoteId = null): QuoteFile;
}
