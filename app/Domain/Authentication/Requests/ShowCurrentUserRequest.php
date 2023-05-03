<?php

namespace App\Domain\Authentication\Requests;

use App\Domain\Authorization\Services\RolePresenter;
use App\Domain\Build\Models\Build;
use App\Domain\Notification\Services\NotificationSettingsPresenter;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ShowCurrentUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function getAdditional(): array
    {
        /** @var NotificationSettingsPresenter $notificationSettingsPresenter */
        $notificationSettingsPresenter = $this->container[NotificationSettingsPresenter::class];
        /** @var RolePresenter $rolePresenter */
        $rolePresenter = $this->container[RolePresenter::class];

        /** @var User $user */
        $user = $this->user();

        return [
            'build' => Build::query()->latest()->firstOrNew()->only(['git_tag', 'build_number']),
            'notification_settings' => $notificationSettingsPresenter->present($user->notification_settings),
            'privileges' => $rolePresenter->presentModules($user->role),
            'role_properties' => $rolePresenter->presentProperties($user->role),
        ];
    }
}
