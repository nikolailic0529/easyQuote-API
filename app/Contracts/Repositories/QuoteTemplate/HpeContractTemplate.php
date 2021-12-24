<?php

namespace App\Contracts\Repositories\QuoteTemplate;

use App\Models\Template\HpeContractTemplate as Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface HpeContractTemplate
{
    /**
     * Paginate existing hpe contract templates.
     *
     * @param string|null $search
     * @return mixed
     */
    public function paginate(?string $search = null);

    /**
     * Retrieve the hpe contract template by specific id or throw not found exception.
     *
     * @param string $id
     * @return Model
     * @throws ModelNotFoundException
     */
    public function findOrFail(string $id): Model;

    /**
     * Retrieve hpe contract templates by passed array clause.
     *
     * @param  array $clause
     * @param  bool|null $activated
     * @param  array $columns
     * @return Collection
     */
    public function findBy(array $clause, ?bool $activated = null, array $columns = ['*']): Collection;

    /**
     * Store a newly created hpe contract template in repository.
     *
     * @param array $attributes
     * @return Model
     */
    public function create(array $attributes): Model;

    /**
     * Update the specified hpe contract template in repository.
     *
     * @param Model|string $id
     * @param array $attributes
     * @return Model
     */
    public function update($id, array $attributes): Model;

    /**
     * Delete the specified hpe contract template from repository.
     *
     * @param Model|string $id
     * @return boolean
     */
    public function delete($id): bool;

    /**
     * Mark as activated the specified hpe contract template in repository.
     *
     * @param Model|string $id
     * @return boolean
     */
    public function activate($id): bool;

    /**
     * Mark as deactivated the specified hpe contract template in repository.
     *
     * @param Model|string $id
     * @return boolean
     */
    public function deactivate($id): bool;

    /**
     * Create a duplicate of the specified hpe contract template in repository.
     *
     * @param Model|string $id
     * @return mixed
     */
    public function copy($id);

    /**
     * Retrieve the hpe contract templates by specified country.
     *
     * @param string $country
     * @return mixed
     */
    public function findByCountry(string $country);
}