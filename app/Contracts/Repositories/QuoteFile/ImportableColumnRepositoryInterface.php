<?php

namespace App\Contracts\Repositories\QuoteFile;

use App\Models\QuoteFile\ImportableColumn;
use Illuminate\Database\Eloquent\Builder;
use Closure;

interface ImportableColumnRepositoryInterface
{
    /**
     * New query builder instance.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(): Builder;

    /**
     * Retrieve the specified Importable Column by id.
     *
     * @param string $id
     * @return \App\Models\QuoteFile\ImportableColumn
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(string $id): ImportableColumn;

    /**
     * Query builder instance for non-temporary columns.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function regularQuery(): Builder;

    /**
     * Get all Importable Columns with aliases.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();

    /**
     * Paginate existing importable columns.
     *
     * @return mixed
     */
    public function paginate();

    /**
     * Search Importable Columns by specified query.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Retrieve all importable columns names.
     *
     * @return mixed
     */
    public function allNames();

    /**
     * Get all System Defined Importable Columns with aliases.
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
     * @return \App\Models\QuoteFile\ImportableColumn
     */
    public function findByName(string $name): ImportableColumn;

    /**
     * Retrieve the first Importable Column matching the attributes or create it.
     *
     * @param array $attributes
     * @param array $values
     * @param Closure $scope
     * @return \App\Models\QuoteFile\ImportableColumn
     */
    public function firstOrCreate(array $attributes, array $values = [], ?Closure $scope = null): ImportableColumn;

    /**
     * Create a new Importable Column with the given attributes.
     *
     * @param array $attributes
     * @return \App\Models\QuoteFile\ImportableColumn
     */
    public function create(array $attributes): ImportableColumn;

    /**
     * Update the specified Importable Column with the given attributes.
     *
     * @param array $attributes
     * @param string $id
     * @return \App\Models\QuoteFile\ImportableColumn
     */
    public function update(array $attributes, string $id): ImportableColumn;

    /**
     * Delete the specified Importable Column.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;

    /**
     * Activate the specified Importable Column.
     *
     * @param string $id
     * @return boolean
     */
    public function activate(string $id): bool;

    /**
     * Deactivate the specified Importable Column.
     *
     * @param string $id
     * @return boolean
     */
    public function deactivate(string $id): bool;
}
