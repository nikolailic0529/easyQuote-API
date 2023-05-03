<?php

namespace App\Domain\Settings\Policies;

use App\Domain\Settings\Models\SystemSetting;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SystemSettingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any system settings.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->can('view_system_settings');
    }

    /**
     * Determine whether the user can view the system setting.
     *
     * @return mixed
     */
    public function view(User $user, SystemSetting $systemSetting)
    {
        return $user->can('view_system_settings');
    }

    /**
     * Determine whether the user can update the system setting.
     *
     * @return mixed
     */
    public function update(User $user, SystemSetting $systemSetting)
    {
        return $user->can('update_system_settings') && !$systemSetting->is_read_only;
    }

    /**
     * Determine whether the user can delete the system setting.
     *
     * @return mixed
     */
    public function delete(User $user, SystemSetting $systemSetting)
    {
        return false;
    }
}
