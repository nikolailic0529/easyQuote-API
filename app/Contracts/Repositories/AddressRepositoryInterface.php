<?php

namespace App\Contracts\Repositories;

use App\Http\Requests\Address\{
    StoreAddressRequest,
    UpdateAddressRequest
};
use Illuminate\Database\Eloquent\Builder;
use App\Models\Address;

interface AddressRepositoryInterface
{
    /**
     * Retrieve all the addresses.
     *
     * @return mixed
     */
    public function all();

    /**
     * Search through all the addresses.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Initialize a new query Builder for Activities.
     *
     * @return Builder
     */
    public function query(): Builder;

    /**
     * Retrieve the specified Address.
     *
     * @param string $id
     * @return Address
     */
    public function find(string $id): Address;

    /**
     * Store a new Address.
     *
     * @param StoreAddressRequest $request
     * @return Address
     */
    public function create(StoreAddressRequest $request): Address;

    /**
     * Update the specified Address.
     *
     * @param UpdateAddressRequest $request
     * @param string $id
     * @return Address
     */
    public function update(UpdateAddressRequest $request, string $id): Address;

    /**
     * Delete the specified Address.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * Activate the specified Address.
     *
     * @param string $id
     * @return bool
     */
    public function activate(string $id): bool;

    /**
     * Deactivate the specified Address.
     *
     * @param string $id
     * @return bool
     */
    public function deactivate(string $id): bool;
}
