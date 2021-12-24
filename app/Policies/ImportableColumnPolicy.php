<?php

namespace App\Policies;

use App\Models\QuoteFile\ImportableColumn;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImportableColumnPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any importable columns.
     *
     * @param \App\Models\User $user
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
     * @param \App\Models\User $user
     * @param \App\Models\QuoteFile\ImportableColumn $importableColumn
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
     * @param \App\Models\User $user
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
     * @param \App\Models\User $user
     * @param \App\Models\QuoteFile\ImportableColumn $importableColumn
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
     * @param \App\Models\User $user
     * @param \App\Models\QuoteFile\ImportableColumn $importableColumn
     * @return mixed
     */
    public function delete(User $user, ImportableColumn $importableColumn)
    {
        $ensureImportableColumnIsNotSystemDefined = fn() => match ((bool)$importableColumn->is_system) {
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
     * @param \App\Models\User $user
     * @param \App\Models\QuoteFile\ImportableColumn $importableColumn
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
     * @param \App\Models\User $user
     * @param \App\Models\QuoteFile\ImportableColumn $importableColumn
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
