<?php

namespace App\Domain\Discount\Contracts;

use App\Domain\Discount\Models\SND;
use App\Domain\Discount\Requests\{UpdateSndRequest};
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
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * SNDs query.
     */
    public function userQuery(): Builder;

    /**
     * Find a specified SND.
     */
    public function find(string $id): SND;

    /**
     * Create SND.
     *
     * @param \App\Domain\Discount\Requests\StoreSndRequest|array $request
     */
    public function create($request): SND;

    /**
     * Update a specified SND.
     *
     * @param \App\Domain\Discount\Requests\UpdateSndRequest $request
     */
    public function update(UpdateSndRequest $request, string $id): SND;

    /**
     * Delete a specified SND.
     */
    public function delete(string $id): bool;

    /**
     * Activate a specified SND.
     */
    public function activate(string $id): bool;

    /**
     * Deactivate a specified SND.
     */
    public function deactivate(string $id): bool;
}
