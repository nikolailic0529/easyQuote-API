<?php

namespace App\Domain\Authentication\Requests;

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
        /** @var NotificationSettingsPresenter $presenter */
        $presenter = $this->container[NotificationSettingsPresenter::class];

        /** @var User $user */
        $user = $this->user();

        return [
            'build' => Build::query()->latest()->firstOrNew()->only(['git_tag', 'build_number']),
            'notification_settings' => $presenter->present($user->notification_settings),
        ];
    }
}
