<?php

namespace App\Domain\QuoteFile\Policies;

use App\Domain\QuoteFile\Models\ImportableColumn;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImportableColumnPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any importable columns.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->hasPermissionTo('view_importable_columns')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the importable column.
     *
     * @return mixed
     */
    public function view(User $user, ImportableColumn $importableColumn)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->hasPermissionTo('view_importable_columns')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create importable columns.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->hasPermissionTo('create_importable_columns')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the importable column.
     *
     * @return mixed
     */
    public function update(User $user, ImportableColumn $importableColumn)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->hasPermissionTo('update_importable_columns')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the importable column.
     *
     * @return mixed
     */
    public function delete(User $user, ImportableColumn $importableColumn)
    {
        $ensureImportableColumnIsNotSystemDefined = fn () => match ((bool) $importableColumn->is_system) {
            true => $this->deny(ICSD_01),
            default => true,
        };

        if ($user->hasRole(R_SUPER)) {
            return $ensureImportableColumnIsNotSystemDefined();
        }

        if ($user->hasPermissionTo('update_importable_columns')) {
            return $ensureImportableColumnIsNotSystemDefined();
        }
    }

    /**
     * Determine whether the user can activate the importable column.
     *
     * @return mixed
     */
    public function activate(User $user, ImportableColumn $importableColumn)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->hasPermissionTo('update_importable_columns')) {
            return true;
        }
    }

    /**
     * Determine whether the user can deactivate the importable column.
     *
     * @return mixed
     */
    public function deactivate(User $user, ImportableColumn $importableColumn)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->hasPermissionTo('update_importable_columns')) {
            return true;
        }
    }
}
