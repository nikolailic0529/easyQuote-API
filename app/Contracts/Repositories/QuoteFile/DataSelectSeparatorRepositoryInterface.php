<?php

namespace App\Contracts\Repositories\QuoteFile;

use Illuminate\Database\Eloquent\Collection;

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

    /**
     * Retrieve all available data select separators.
     *
     * @return Collection
     */
    public function all(): Collection;
}
