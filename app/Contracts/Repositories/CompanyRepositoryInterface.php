<?php namespace App\Contracts\Repositories;

use App\Builder\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Company \ {
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
     * Get all User's Companies.
     *
     * @return Paginator
     */
    public function all(): Paginator;

    /**
     * Search over User's Companies.
     *
     * @param string $query
     * @return Paginator
     */
    public function search(string $query = ''): Paginator;

    /**
     * User's Companies query.
     *
     * @return Builder
     */
    public function userQuery(): Builder;

    /**
     * Find User's Company.
     *
     * @param string $id
     * @return Company
     */
    public function find(string $id): Company;

    /**
     * Create User's Company.
     *
     * @param StoreCompanyRequest $request
     * @return Company
     */
    public function create(StoreCompanyRequest $request): Company;

    /**
     * Update User's Company.
     *
     * @param UpdateCompanyRequest $request
     * @param string $id
     * @return Company
     */
    public function update(UpdateCompanyRequest $request, string $id): Company;

    /**
     * Delete User's Company.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;

    /**
     * Activate User's Company.
     *
     * @param string $id
     * @return boolean
     */
    public function activate(string $id): bool;

    /**
     * Deactivate User's Company.
     *
     * @param string $id
     * @return boolean
     */
    public function deactivate(string $id): bool;
}
