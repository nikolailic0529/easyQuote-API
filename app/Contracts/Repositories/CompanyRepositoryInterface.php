<?php

namespace App\Contracts\Repositories;

use App\Builder\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Company\{
    StoreCompanyRequest,
    UpdateCompanyRequest
};
use App\Models\Company;
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
     * @return Company
     */
    public function find(string $id): Company;

    /**
     * Create Company.
     *
     * @param StoreCompanyRequest $request
     * @return Company
     */
    public function create(StoreCompanyRequest $request): Company;

    /**
     * Update Company.
     *
     * @param UpdateCompanyRequest $request
     * @param string $id
     * @return Company
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
