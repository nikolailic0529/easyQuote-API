<?php

namespace App\Contracts\Repositories\Quote\Discount;

use App\Http\Requests\Discount\{
    StoreSNDrequest,
    UpdateSNDrequest
};
use App\Models\Quote\Discount\SND;
use Illuminate\Database\Eloquent\Builder;

interface SNDrepositoryInterface
{
    /**
     * Get all SNDs.
     *
     * @return mixed
     */
    public function all();

    /**
     * Search over SNDs.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * SNDs query.
     *
     * @return Builder
     */
    public function userQuery(): Builder;

    /**
     * Find a specified SND.
     *
     * @param string $id
     * @return \App\Models\Quote\Discount\SND
     */
    public function find(string $id): SND;

    /**
     * Create SND.
     *
     * @param \App\Http\Requests\Discount\StoreSNDrequest|array $request
     * @return \App\Models\Quote\Discount\SND
     */
    public function create($request): SND;

    /**
     * Update a specified SND.
     *
     * @param \App\Http\Requests\Discount\UpdateSNDrequest $request
     * @param string $id
     * @return \App\Models\Quote\Discount\SND
     */
    public function update(UpdateSNDrequest $request, string $id): SND;

    /**
     * Delete a specified SND.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;

    /**
     * Activate a specified SND.
     *
     * @param string $id
     * @return boolean
     */
    public function activate(string $id): bool;

    /**
     * Deactivate a specified SND.
     *
     * @param string $id
     * @return boolean
     */
    public function deactivate(string $id): bool;
}
