<?php

namespace App\Domain\Template\Contracts;

use App\Domain\HpeContract\Models\HpeContractTemplate as Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface HpeContractTemplate
{
    /**
     * Paginate existing hpe contract templates.
     *
     * @return mixed
     */
    public function paginate(?string $search = null);

    /**
     * Retrieve the hpe contract template by specific id or throw not found exception.
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(string $id): Model;

    /**
     * Retrieve hpe contract templates by passed array clause.
     */
    public function findBy(array $clause, ?bool $activated = null, array $columns = ['*']): Collection;

    /**
     * Store a newly created hpe contract template in repository.
     */
    public function create(array $attributes): Model;

    /**
     * Update the specified hpe contract template in repository.
     *
     * @param Model|string $id
     */
    public function update($id, array $attributes): Model;

    /**
     * Delete the specified hpe contract template from repository.
     *
     * @param Model|string $id
     */
    public function delete($id): bool;

    /**
     * Mark as activated the specified hpe contract template in repository.
     *
     * @param Model|string $id
     */
    public function activate($id): bool;

    /**
     * Mark as deactivated the specified hpe contract template in repository.
     *
     * @param Model|string $id
     */
    public function deactivate($id): bool;

    /**
     * Create a duplicate of the specified hpe contract template in repository.
     *
     * @param Model|string $id
     *
     * @return mixed
     */
    public function copy($id);

    /**
     * Retrieve the hpe contract templates by specified country.
     *
     * @return mixed
     */
    public function findByCountry(string $country);
}
