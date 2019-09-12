<?php namespace App\Contracts\Repositories\QuoteFile;

interface DataSelectSeparatorRepositoryInterface
{
    /**
     * Find DataSelectSeparator by id
     *
     * @param string $id
     * @return \App\Models\QuoteFile\DataSelectSeparator
     */
    public function find(string $id);

    /**
     * Find DataSelectSeparator by separator string
     *
     * @param string $separator
     * @return \App\Models\QuoteFile\DataSelectSeparator
     */
    public function findBySeparator(string $separator);
}