<?php namespace App\Contracts\Repositories\Quote\Discount;

use App\Builder\Pagination\Paginator;
use App\Http\Requests\Discount \ {
    StoreSNDrequest,
    UpdateSNDrequest
};
use App\Models\Quote\Discount\SND;
use Illuminate\Database\Eloquent\Builder;

interface SNDrepositoryInterface
{
    /**
     * Get all User's SNDs.
     *
     * @return Paginator
     */
    public function all(): Paginator;

    /**
     * Search over User's SNDs.
     *
     * @param string $query
     * @return Paginator
     */
    public function search(string $query = ''): Paginator;

    /**
     * User's SNDs query.
     *
     * @return Builder
     */
    public function userQuery(): Builder;

    /**
     * Find User's SND.
     *
     * @param string $id
     * @return SND
     */
    public function find(string $id): SND;

    /**
     * Create User's SND.
     *
     * @param StoreSNDrequest $request
     * @return SND
     */
    public function create(StoreSNDrequest $request): SND;

    /**
     * Update User's SND.
     *
     * @param UpdateSNDrequest $request
     * @param string $id
     * @return SND
     */
    public function update(UpdateSNDrequest $request, string $id): SND;

    /**
     * Delete User's SND.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;

    /**
     * Activate User's SND.
     *
     * @param string $id
     * @return boolean
     */
    public function activate(string $id): bool;

    /**
     * Deactivate User's SND.
     *
     * @param string $id
     * @return boolean
     */
    public function deactivate(string $id): bool;
}
