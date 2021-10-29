<?php

namespace App\Contracts\Services;

use App\Models\User;

interface PermissionBroker
{
    /**
     * Retrieve access level from the user direct permissions by specific granted module.
     * If provider is not passed, authenticated user will be used as provider.
     *
     * @param string $module
     * @param User $user
     * @param User|null $provider
     * @return string|null
     */
    public function grantedModuleLevel(string $module, User $user, ?User $provider = null);

    /**
     * Grant module permission to specific users.
     *
     * @param array $users
     * @param string $module
     * @param string $level
     * @return mixed
     */
    public function grantModulePermission(array $users, string $module, string $level);

    /**
     * Get provided modules.
     *
     * @return array
     */
    public function providedModules(): array;

    /**
     * Get provided access levels.
     *
     * @return array
     */
    public function providedLevels(): array;

    /**
     * Get default guard name.
     *
     * @return string
     */
    public function getDefaultGuard(): string;

    /**
     * Give permission to user.
     *
     * @param User $user
     * @param string $name
     * @param string|null $guard
     */
    public function givePermissionToUser(User $user, string $name, ?string $guard = null): void;
}