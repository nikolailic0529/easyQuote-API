<?php

namespace App\Contracts\Repositories;

use App\Builder\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Vendor\{
    StoreVendorRequest,
    UpdateVendorRequest
};
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;

interface VendorRepositoryInterface
{
    /**
     * Get all User's Vendors.
     *
     * @return Paginator
     */
    public function all(): Paginator;

    /**
     * Get All User's Vendors without pagination
     *
     * @return Collection
     */
    public function allFlatten(): Collection;

    /**
     * Search over User's Vendors.
     *
     * @param string $query
     * @return Paginator
     */
    public function search(string $query = ''): Paginator;

    /**
     * User's Vendors query.
     *
     * @return Builder
     */
    public function userQuery(): Builder;

    /**
     * Find User's Vendor.
     *
     * @param string $id
     * @return Vendor
     */
    public function find(string $id): Vendor;

    /**
     * Create User's Vendor.
     *
     * @param StoreVendorRequest $request
     * @return Vendor
     */
    public function create(StoreVendorRequest $request): Vendor;

    /**
     * Update User's Vendor.
     *
     * @param UpdateVendorRequest $request
     * @param string $id
     * @return Vendor
     */
    public function update(UpdateVendorRequest $request, string $id): Vendor;

    /**
     * Delete User's Vendor.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;

    /**
     * Activate User's Vendor.
     *
     * @param string $id
     * @return boolean
     */
    public function activate(string $id): bool;

    /**
     * Deactivate User's Vendor.
     *
     * @param string $id
     * @return boolean
     */
    public function deactivate(string $id): bool;

    /**
     * Find User's Vendors by Country
     *
     * @param string $id
     * @return Collection
     */
    public function country(string $id): Collection;
}
