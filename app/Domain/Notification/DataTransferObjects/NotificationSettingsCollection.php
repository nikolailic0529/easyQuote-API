<?php

namespace App\Domain\Notification\DataTransferObjects;

use Illuminate\Support\Collection;

final class NotificationSettingsCollection extends Collection
{
    public function get($key, $default = null): NotificationSettingsGroupData
    {
        return parent::get($key, $default);
    }
}
