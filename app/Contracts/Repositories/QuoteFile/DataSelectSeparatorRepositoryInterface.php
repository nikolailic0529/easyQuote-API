<?php

namespace App\Contracts\Repositories\QuoteFile;

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
     * Find DataSelectSeparator by name string
     *
     * @param string $name
     * @return \App\Models\QuoteFile\DataSelectSeparator
     */
    public function findByName(string $name);
}
