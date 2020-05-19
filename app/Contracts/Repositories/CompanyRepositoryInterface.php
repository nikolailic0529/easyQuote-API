<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Company\{
    StoreCompanyRequest,
    UpdateCompanyRequest
};
use App\Models\Company;
use Closure;
use Illuminate\Database\Eloquent\Collection as IlluminateCollection;
use Illuminate\Support\Collection;

interface CompanyRepositoryInterface
{
    /**
     * Data for creating a new Company
     *
     * @param $array
     * @return Collection
     */
    public function data($additionalData = []): Collection;

    /**
     * Get all Companies.
     *
     * @return mixed
     */
    public function all();

    /**
     * Retrieve all Companies with associated Vendors and Countries.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function allWithVendorsAndCountries(): IlluminateCollection;

    /**
     * Retrieve all Internal type Companies with associated Vendors and Countries.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function allInternalWithVendorsAndCountries(): IlluminateCollection;

    /**
     * Search over Companies.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Search external companies by given part of name.
     *
     * @param string|null $query
     * @param int $limit
     * @return mixed
     */
    public function searchExternal(?string $query, int $limit = 15);

    /**
     * Retrieve all External type companies.
     *
     * @return IlluminateCollection
     */
    public function allExternal(): IlluminateCollection;

    /**
     * Companies query.
     *
     * @return Builder
     */
    public function userQuery(): Builder;

    /**
     * Count companies by specific clause.
     *
     * @param array $where
     * @return integer
     */
    public function count(array $where = []): int;

    /**
     * Find Company.
     *
     * @param string $id
     * @return \App\Models\Company
     */
    public function find(string $id): Company;

    /**
     * Retrieve a company by specified vat.
     *
     * @param string $vat
     * @return \App\Models\Company|null
     */
    public function findByVat(string $vat);

    /**
     * Retrieve random existing Company.
     *
     * @param int $limit
     * @param Closure $scope
     * @return \App\Models\Company|\Illuminate\Database\Eloquent\Collection|null
     */
    public function random(int $limit = 1, ?Closure $scope = null);

    /**
     * Create Company.
     *
     * @param  array $attributes
     * @return \App\Models\Company
     */
    public function create(array $attributes): Company;

    /**
     * Update Company.
     *
     * @param string $id
     * @param array $attributes
     * @return \App\Models\Company
     */
    public function update(string $id, array $attributes): Company;

    /**
     * Delete Company.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;

    /**
     * Activate Company.
     *
     * @param string $id
     * @return boolean
     */
    public function activate(string $id): bool;

    /**
     * Deactivate Company.
     *
     * @param string $id
     * @return boolean
     */
    public function deactivate(string $id): bool;
}
