<?php

namespace App\Contracts\Repositories;

use App\Models\Role;
use App\Http\Requests\Role\{
    StoreRoleRequest,
    UpdateRoleRequest
};
use App\Builder\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface RoleRepositoryInterface
{
    /**
     * Data for creating a new Role
     *
     * @param $array
     * @return Collection
     */
    public function data(): Collection;

    /**
     * Get all Collaboration Roles.
     *
     * @return mixed
     */
    public function all();

    /**
     * Search over Collaboration Roles.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Collaboration Roles query.
     *
     * @return Builder
     */
    public function userQuery(): Builder;

    /**
     * Find Collaboration Role.
     *
     * @param string $id
     * @return Role
     */
    public function find(string $id): Role;

    /**
     * Find Role by specified name.
     *
     * @param string $name
     * @return Role
     */
    public function findByName(string $name): Role;

    /**
     * Create Collaboration Role.
     *
     * @param \App\Http\Requests\Role\StoreRoleRequest|array $attributes
     * @return Role
     */
    public function create($attributes): Role;

    /**
     * Update Collaboration Role.
     *
     * @param \App\Http\Requests\Role\UpdateRoleRequest $request
     * @param string $id
     * @return Role
     */
    public function update(UpdateRoleRequest $request, string $id): Role;

    /**
     * Delete Collaboration Role.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;

    /**
     * Activate Collaboration Role.
     *
     * @param string $id
     * @return boolean
     */
    public function activate(string $id): bool;

    /**
     * Deactivate Collaboration Role.
     *
     * @param string $id
     * @return boolean
     */
    public function deactivate(string $id): bool;
}
