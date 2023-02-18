<?php

namespace App\Domain\Authorization\Contracts;

use App\Domain\User\Models\User;

interface PermissionBroker
{
    /**
     * Retrieve access level from the user direct permissions by specific granted module.
     * If provider is not passed, authenticated user will be used as provider.
     *
     * @return string|null
     */
    public function grantedModuleLevel(string $module, User $user, ?User $provider = null);

    /**
     * Grant module permission to specific users.
     *
     * @return mixed
     */
    public function grantModulePermission(array $users, string $module, string $level);

    /**
     * Get provided modules.
     */
    public function providedModules(): array;

    /**
     * Get provided access levels.
     */
    public function providedLevels(): array;

    /**
     * Get default guard name.
     */
    public function getDefaultGuard(): string;

    /**
     * Give permission to user.
     */
    public function givePermissionToUser(User $user, string $name, ?string $guard = null): void;
}
