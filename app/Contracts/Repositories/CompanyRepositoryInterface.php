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
     * @param array $additionalDataData
     * @return Collection
     */
    public function data(array $additionalDataData = []): Collection;

    /**
     * Get all Companies.
     *
     * @return mixed
     */
    public function all();

    /**
     * Retrieve all Internal type Companies.
     *
     * @param  array $columns
     * @return IlluminateCollection
     */
    public function allInternal(array $columns = ['*']): IlluminateCollection;

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
     * Retrieve all Internal type Companies with associated Countries.
     *
     * @param  array $columns
     * @return IlluminateCollection
     */
    public function allInternalWithCountries(array $columns = ['*']): IlluminateCollection;

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
     * @param array $where
     * @return IlluminateCollection
     */
    public function allExternal(array $where = []): IlluminateCollection;

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
}
