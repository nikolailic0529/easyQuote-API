<?php

namespace App\Domain\Rescue\Contracts;

use App\Domain\Rescue\Models\Contract;
use Illuminate\Database\Eloquent\Builder;

interface ContractDraftedRepositoryInterface
{
    /**
     * Paginate existing drafted Contracts.
     *
     * @return mixed
     */
    public function paginate();

    /**
     * Search by drafted Contracts.
     *
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Initialize a new query Builder with user scope.
     */
    public function userQuery(): Builder;

    /**
     * Find the specified Drafted Contract by the given ID.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(string $id): Contract;

    /**
     * Delete the specified drafted Contract.
     */
    public function delete(string $id): bool;

    /**
     * Activate the specified drafted Contract.
     */
    public function activate(string $id): bool;

    /**
     * Deactivate the specified drafted Contract.
     */
    public function deactivate(string $id): bool;

    /**
     * Submit the specified drafted Contract and update the given attributes.
     */
    public function submit(string $id, array $attributes = []): bool;
}
