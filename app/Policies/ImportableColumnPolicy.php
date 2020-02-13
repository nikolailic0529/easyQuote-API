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
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->can('view_system_settings');
    }

    /**
     * Determine whether the user can view the importable column.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteFile\ImportableColumn  $importableColumn
     * @return mixed
     */
    public function view(User $user, ImportableColumn $importableColumn)
    {
        return $user->can('view_system_settings');
    }

    /**
     * Determine whether the user can create importable columns.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->can('update_system_settings');
    }

    /**
     * Determine whether the user can update the importable column.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteFile\ImportableColumn  $importableColumn
     * @return mixed
     */
    public function update(User $user, ImportableColumn $importableColumn)
    {
        return $user->can('update_system_settings');
    }

    /**
     * Determine whether the user can delete the importable column.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteFile\ImportableColumn  $importableColumn
     * @return mixed
     */
    public function delete(User $user, ImportableColumn $importableColumn)
    {
        if ($user->can('update_system_settings') && $importableColumn->isSystem()) {
            return $this->deny(ICSD_01);
        }

        return $user->can('update_system_settings');
    }

    /**
     * Determine whether the user can activate the importable column.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteFile\ImportableColumn  $importableColumn
     * @return mixed
     */
    public function activate(User $user, ImportableColumn $importableColumn)
    {
        if (!$this->update($user, $importableColumn)) {
            return false;
        }

        if ($importableColumn->isSystem()) {
            return $this->deny(ICSU_01);
        }

        return true;
    }

    /**
     * Determine whether the user can deactivate the importable column.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteFile\ImportableColumn  $importableColumn
     * @return mixed
     */
    public function deactivate(User $user, ImportableColumn $importableColumn)
    {
        if (!$this->update($user, $importableColumn)) {
            return false;
        }

        if ($importableColumn->isSystem()) {
            return $this->deny(ICSU_01);
        }

        return true;
    }
}
