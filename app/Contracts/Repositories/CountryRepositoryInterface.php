<?php

namespace App\Contracts\Repositories;

use App\Models\Data\Country;
use Illuminate\Database\Eloquent\Builder;
use Closure;

interface CountryRepositoryInterface
{
    /**
     * Get all timezones
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();

    /**
     * Make a new Eloquent Query Builder instance.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(): Builder;

    /**
     * Retrieve Country by passed Country ISO code.
     *
     * @param string|null $code
     * @return string|null
     */
    public function findByCode(?string $code);

    /**
     * Retrieve Country Id by passed Country ISO code.
     *
     * @param string|array $code
     * @return string|null
     */
    public function findIdByCode($code);

    /**
     * Retrieve random existing Country.
     *
     * @param int $limit
     * @param \Closure $scope
     * @return \App\Models\Data\Country|\Illuminate\Database\Eloquent\Collection|null
     */
    public function random(int $limit = 1, ?Closure $scope = null);

    /**
     * Paginate existing Countries.
     *
     * @return mixed
     */
    public function paginate();

    /**
     * Search through existing Countries.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Retrieve a Country with specified ID.
     *
     * @param string $id
     * @return \App\Models\Data\Country
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(string $id): Country;

    /**
     * Create a new Country with the given attributes.
     *
     * @param array $attributes
     * @return \App\Models\Data\Country
     */
    public function create(array $attributes): Country;

    /**
     * Update the specified Country with the given attributes.
     *
     * @param array $attributes
     * @param string $id
     * @return \App\Models\Data\Country
     */
    public function update(array $attributes, string $id): Country;

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return \App\Models\Data\Country
     */
    public function updateOrCreate(array $attributes, array $values = []): Country;

    /**
     * Delete a Country with specified ID from the repository.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;

    /**
     * Activate the specified Country.
     *
     * @param string $id
     * @return boolean
     */
    public function activate(string $id): bool;

    /**
     * Activate the specified Country.
     *
     * @param string $id
     * @return boolean
     */
    public function deactivate(string $id): bool;
}
