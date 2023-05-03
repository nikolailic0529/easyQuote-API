<?php

namespace App\Domain\Rescue\Contracts;

use App\Domain\Rescue\Models\Contract;
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
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Initialize a new query Builder with user scope.
     */
    public function userQuery(): Builder;

    /**
     * Find the specified submitted Contract.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(string $id): Contract;

    /**
     * Delete the specified submitted Contract.
     */
    public function delete(string $id): bool;

    /**
     * Activate the specified submitted Contract.
     */
    public function activate(string $id): bool;

    /**
     * Deactivate the specified submitted Contract.
     */
    public function deactivate(string $id): bool;

    /**
     * UnSubmit the specified Contract.
     */
    public function unSubmit(string $id): bool;
}
