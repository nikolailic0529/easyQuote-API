<?php

namespace App\Contracts\Repositories\Quote;

use App\Models\Quote\Contract;
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
     * Find the specified Drafted Contract by the given ID.
     *
     * @param string $id
     * @return \App\Models\Quote\Contract
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(string $id): Contract;

    /**
     * Delete the specified drafted Contract.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;

    /**
     * Activate the specified drafted Contract.
     *
     * @param string $id
     * @return boolean
     */
    public function activate(string $id): bool;

    /**
     * Deactivate the specified drafted Contract.
     *
     * @param string $id
     * @return boolean
     */
    public function deactivate(string $id): bool;

    /**
     * Submit the specified drafted Contract and update the given attributes.
     *
     * @param string $id
     * @param array $attributes
     * @return boolean
     */
    public function submit(string $id, array $attributes = []): bool;
}
