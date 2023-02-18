<?php

namespace App\Domain\Vendor\Contracts;

use App\Domain\Vendor\Models\Vendor;
use App\Domain\Vendor\Requests\{UpdateVendorRequest};
use Illuminate\Database\Eloquent\Builder;
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
     * Retrieve all vendors from cache.
     *
     * @return mixed
     */
    public function allCached();

    /**
     * Search over Vendors.
     *
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Vendors query.
     */
    public function userQuery(): Builder;

    /**
     * Find Vendor.
     */
    public function find(string $id): Vendor;

    /**
     * Retrieve the specific vendor from cache by id.
     */
    public function findCached(string $id): ?Vendor;

    /**
     * Retrieve a vendor by specified short code.
     *
     * @param string|array $code
     *
     * @return \App\Domain\Vendor\Models\Vendor|null
     */
    public function findByCode($code);

    /**
     * Retrieve random existing Vendor.
     *
     * @return \App\Domain\Vendor\Models\Vendor|\Illuminate\Database\Eloquent\Collection|null
     */
    public function random(int $limit = 1, ?\Closure $scope = null);

    /**
     * Create Vendor.
     *
     * @param \App\Domain\Vendor\Requests\StoreVendorRequest|array $request
     */
    public function create($request): Vendor;

    /**
     * Update Vendor.
     *
     * @param \App\Domain\Vendor\Requests\UpdateVendorRequest $request
     */
    public function update(UpdateVendorRequest $request, string $id): Vendor;

    /**
     * Delete Vendor.
     */
    public function delete(string $id): bool;

    /**
     * Activate Vendor.
     */
    public function activate(string $id): bool;

    /**
     * Deactivate Vendor.
     */
    public function deactivate(string $id): bool;

    /**
     * Find Vendors by Country.
     */
    public function country(string $id): Collection;
}
