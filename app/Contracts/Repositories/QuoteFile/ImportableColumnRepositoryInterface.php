<?php namespace App\Contracts\Repositories\QuoteFile;

interface ImportableColumnRepositoryInterface
{
    /**
     * Get all columns regexps ordered by order field
     *
     * @return \Illuminate\Support\Collection
     */
    public function allColumnsRegs();

    /**
     * Get all columns with aliases
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();

    /**
     * Get all importable columns names
     *
     * @return Array
     */
    public function allNames();
}
