<?php

namespace App\Contracts\Repositories\Contract;

use App\Models\Quote\Contract;
use Illuminate\Database\Eloquent\Builder;

interface ContractSubmittedRepositoryInterface
{
    /**
     * Paginate existing submitted Contracts.
     *
     * @return mixed
     */
    public function paginate();

    /**
     * Search by existing submitted Contracts.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Initialize a new query Builder with user scope.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function userQuery(): Builder;

    /**
     * Find the specified submitted Contract.
     *
     * @param string $id
     * @return \App\Models\Quote\Contract
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(string $id): Contract;

    /**
     * Delete the specified submitted Contract.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;

    /**
     * Activate the specified submitted Contract.
     *
     * @param string $id
     * @return boolean
     */
    public function activate(string $id): bool;

    /**
     * Deactivate the specified submitted Contract.
     *
     * @param string $id
     * @return boolean
     */
    public function deactivate(string $id): bool;

    /**
     * UnSubmit the specified Contract.
     *
     * @param string $id
     * @return boolean
     */
    public function unSubmit(string $id): bool;
}
