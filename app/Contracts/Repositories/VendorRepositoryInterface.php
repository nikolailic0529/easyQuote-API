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
     * Get all Vendors.
     *
     * @return mixed
     */
    public function all();

    /**
     * Get All Vendors without pagination
     *
     * @return Collection
     */
    public function allFlatten(): Collection;

    /**
     * Search over Vendors.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Vendors query.
     *
     * @return Builder
     */
    public function userQuery(): Builder;

    /**
     * Find Vendor.
     *
     * @param string $id
     * @return Vendor
     */
    public function find(string $id): Vendor;

    /**
     * Create Vendor.
     *
     * @param StoreVendorRequest $request
     * @return Vendor
     */
    public function create(StoreVendorRequest $request): Vendor;

    /**
     * Update Vendor.
     *
     * @param UpdateVendorRequest $request
     * @param string $id
     * @return Vendor
     */
    public function update(UpdateVendorRequest $request, string $id): Vendor;

    /**
     * Delete Vendor.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;

    /**
     * Activate Vendor.
     *
     * @param string $id
     * @return boolean
     */
    public function activate(string $id): bool;

    /**
     * Deactivate Vendor.
     *
     * @param string $id
     * @return boolean
     */
    public function deactivate(string $id): bool;

    /**
     * Find Vendors by Country.
     *
     * @param string $id
     * @return Collection
     */
    public function country(string $id): Collection;
}
