<?php

namespace App\Contracts\Repositories;

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
     * @return \Illuminate\Database\Eloquent\Collection
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
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function userQuery(): Builder;

    /**
     * Find Vendor.
     *
     * @param string $id
     * @return \App\Models\Vendor
     */
    public function find(string $id): Vendor;

    /**
     * Retrieve a vendor by specified short code.
     *
     * @param string|array $code
     * @return \App\Models\Vendor|null
     */
    public function findByCode($code);

    /**
     * Retrieve random existing Vendor.
     *
     * @param int $limit
     * @return \App\Models\Vendor|\Illuminate\Database\Eloquent\Collection|null
     */
    public function random(int $limit = 1);

    /**
     * Create Vendor.
     *
     * @param \App\Http\Requests\Vendor\StoreVendorRequest|array $request
     * @return \App\Models\Vendor
     */
    public function create($request): Vendor;

    /**
     * Update Vendor.
     *
     * @param \App\Http\Requests\Vendor\UpdateVendorRequest $request
     * @param string $id
     * @return \App\Models\Vendor
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
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function country(string $id): Collection;
}
