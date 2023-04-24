<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\DataTransferObjects\NotificationSettings\NotificationSettingsData;
use App\Domain\Notification\DataTransferObjects\NotificationSettingsCollection;
use App\Domain\Notification\DataTransferObjects\NotificationSettingsGroupData;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class NotificationSettingsPresenter
{
    public function __construct(
        protected readonly array $config,
    ) {
    }

    public function present(NotificationSettingsData $settings): NotificationSettingsCollection
    {
        $settingsArray = $settings->toArray();

        $collection = NotificationSettingsCollection::empty();

        foreach ($this->config['settings'] as $k => $controls) {
            $collection->push(NotificationSettingsGroupData::from([
                'label' => $this->resolveGroupLabel($k),
                'key' => $k,
                'controls' => $this->presentControls($controls, $settingsArray[$k] ?? []),
            ]));
        }

        return $collection;
    }

    protected function presentControls(array $keys, array $groupSettings): array
    {
        $controls = [];

        foreach ($keys as $key) {
            $controls[] = [
                'label' => $this->resolveControlLabel($key),
                'key' => $key,
                'email_notif' => (bool) Arr::get($groupSettings, "$key.email_notif", false),
                'app_notif' => (bool) Arr::get($groupSettings, "$key.app_notif", true),
            ];
        }

        return $controls;
    }

    protected function resolveGroupLabel(string $key): string
    {
        $transKey = "notification.settings.groups.$key";

        $label = __($transKey);

        if ($label === $transKey) {
            return Str::headline($key);
        }

        return $label;
    }

    protected function resolveControlLabel(string $key): string
    {
        $transKey = "notification.settings.controls.$key";

        $label = __($transKey);

        if ($label === $transKey) {
            return Str::headline($key);
        }

        return $label;
    }
}
