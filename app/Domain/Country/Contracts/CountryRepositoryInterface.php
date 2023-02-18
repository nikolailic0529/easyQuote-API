<?php

namespace App\Domain\Country\Contracts;

use App\Domain\Country\Models\Country;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface CountryRepositoryInterface
{
    /**
     * Get all countries.
     */
    public function all(): Collection;

    /**
     * Get all cached countries.
     */
    public function allCached(): Collection;

    /**
     * Make a new Eloquent Query Builder instance.
     */
    public function query(): Builder;

    /**
     * Retrieve cached country from repository.
     */
    public function findCached(string $id): ?Country;

    /**
     * Retrieve Country by passed Country ISO code.
     *
     * @return string|null
     */
    public function findByCode(?string $code);

    /**
     * Retrieve Country Id by passed Country ISO code.
     *
     * @param string|array $code
     *
     * @return string|null
     */
    public function findIdByCode($code);

    /**
     * Retrieve Countries by specified clause.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findBy(array $where, array $columns = ['*']);

    /**
     * Retrieve Countries belonging to the specified Vendor.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByVendor(string $vendor, array $columns = ['*']);

    /**
     * Retrieve Countries belonging to the specified Company.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByCompany(string $company, array $columns = ['*']);

    /**
     * Retrieve random existing Country.
     *
     * @param \Closure $scope
     *
     * @return \App\Domain\Country\Models\Country|\Illuminate\Database\Eloquent\Collection|null
     */
    public function random(int $limit = 1, ?\Closure $scope = null);

    /**
     * Paginate existing Countries.
     *
     * @return mixed
     */
    public function paginate();

    /**
     * Search through existing Countries.
     *
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Retrieve a Country with specified ID.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(string $id): Country;

    /**
     * Create a new Country with the given attributes.
     */
    public function create(array $attributes): Country;

    /**
     * Update the specified Country with the given attributes.
     */
    public function update(array $attributes, string $id): Country;

    /**
     * Create or update a record matching the attributes, and fill it with values.
     */
    public function updateOrCreate(array $attributes, array $values = []): Country;

    /**
     * Delete a Country with specified ID from the repository.
     */
    public function delete(string $id): bool;

    /**
     * Activate the specified Country.
     */
    public function activate(string $id): bool;

    /**
     * Activate the specified Country.
     */
    public function deactivate(string $id): bool;
}
