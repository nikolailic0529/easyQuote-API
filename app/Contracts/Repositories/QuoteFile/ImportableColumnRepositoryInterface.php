<?php

namespace App\Contracts\Repositories\QuoteFile;

use App\Models\QuoteFile\ImportableColumn;
use Closure;

interface ImportableColumnRepositoryInterface
{
    /**
     * Get all Importable Columns with aliases
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

    /**
     * Get all System Defined Importable Columns with aliases
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function allSystem();

    /**
     * Get all User defined Importable Columns matching the aliases.
     *
     * @param array $aliases
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function userColumns(array $aliases = []);

    /**
     * Find ImportableColumn by Name
     *
     * @param string $name
     * @return ImportableColumn
     */
    public function findByName(string $name): ImportableColumn;

    /**
     * Retrieve the first Importable Column matching the attributes or create it.
     *
     * @param array $attributes
     * @param array $values
     * @param Closure $scope
     * @return ImportableColumn
     */
    public function firstOrCreate(array $attributes, array $values = [], ?Closure $scope = null): ImportableColumn;
}
