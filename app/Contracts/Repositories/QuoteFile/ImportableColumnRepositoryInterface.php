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
     * Get all columns aliases
     *
     * @return Array
     */
    public function allColumnsAliases();
}
