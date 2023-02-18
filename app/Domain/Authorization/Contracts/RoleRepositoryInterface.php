<?php

namespace App\Domain\Authorization\Contracts;

use App\Domain\Authorization\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as IlluminateCollection;

interface RoleRepositoryInterface
{
    /**
     * Get all Collaboration Roles.
     *
     * @return mixed
     */
    public function all();

    /**
     * Retieve all activated roles.
     */
    public function allActivated(array $columns = ['*']): IlluminateCollection;

    /**
     * Retrieve all non-system roles.
     */
    public function allNonSystem(array $columns = ['*']): IlluminateCollection;

    /**
     * Search over Collaboration Roles.
     *
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Collaboration Roles query.
     */
    public function userQuery(): Builder;

    /**
     * Find role.
     */
    public function find(string $id): Role;

    /**
     * Find roles having minimal access to the specific module.
     *
     * @return mixed
     */
    public function findByModule(string $module, ?\Closure $scope = null);

    /**
     * Find Role by specified name.
     */
    public function findByName(string $name): Role;

    /**
     * Create Collaboration Role.
     */
    public function create(array $attributes): Role;

    /**
     * Update Collaboration Role.
     */
    public function update(string $id, array $attributes): Role;

    /**
     * Delete Collaboration Role.
     */
    public function delete(string $id): bool;

    /**
     * Activate Collaboration Role.
     */
    public function activate(string $id): bool;

    /**
     * Deactivate Collaboration Role.
     */
    public function deactivate(string $id): bool;
}
