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
     * Search over Companies.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Companies query.
     *
     * @return Builder
     */
    public function userQuery(): Builder;

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
     * @param \App\Http\Requests\Company\StoreCompanyRequest|array $request
     * @return \App\Models\Company
     */
    public function create($request): Company;

    /**
     * Update Company.
     *
     * @param \App\Http\Requests\Company\UpdateCompanyRequest $request
     * @param string $id
     * @return \App\Models\Company
     */
    public function update(UpdateCompanyRequest $request, string $id): Company;

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
